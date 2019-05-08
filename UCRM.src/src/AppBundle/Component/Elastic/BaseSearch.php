<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic;

use AppBundle\Component\Elastic\QueryFactory\BaseQueryFactory;
use AppBundle\Component\Elastic\QueryFactory\ClientQueryFactory;
use AppBundle\Component\Elastic\QueryFactory\DeviceQueryFactory;
use AppBundle\Component\Elastic\QueryFactory\HelpQueryFactory;
use AppBundle\Component\Elastic\QueryFactory\InvoiceQueryFactory;
use AppBundle\Component\Elastic\QueryFactory\NavigationQueryFactory;
use AppBundle\Component\Elastic\QueryFactory\PaymentQueryFactory;
use AppBundle\Component\Elastic\QueryFactory\QuoteQueryFactory;
use AppBundle\Component\Elastic\QueryFactory\SiteQueryFactory;
use AppBundle\Component\Elastic\QueryFactory\TicketQueryFactory;
use AppBundle\Controller\ClientController;
use AppBundle\Controller\DeviceController;
use AppBundle\Controller\InvoiceController;
use AppBundle\Controller\PaymentController;
use AppBundle\Controller\QuoteController;
use AppBundle\Entity;
use AppBundle\Exception\ElasticsearchException;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionGrantedChecker;
use Elastica\Type;
use FOS\ElasticaBundle\Index\IndexManager;
use FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use Nette\Utils\Strings;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use TicketingBundle\Controller\TicketController;
use TicketingBundle\Entity\Ticket;

abstract class BaseSearch
{
    const TYPE_CLIENT = 'client';
    const TYPE_DEVICE = 'device';
    const TYPE_HELP = 'help';
    const TYPE_INVOICE = 'invoice';
    const TYPE_PAYMENT = 'payment';
    const TYPE_SITE = 'site';
    const TYPE_TICKET = 'ticket';
    const TYPE_QUOTE = 'quote';
    const TYPE_NAVIGATION = 'navigation';

    /**
     * @var ElasticaToModelTransformerInterface[]
     */
    protected $transformers;

    /**
     * @var Client
     */
    protected $elasticClient;

    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * @var PermissionGrantedChecker
     */
    protected $permissionGrantedChecker;

    /**
     * @var ClientQueryFactory
     */
    protected $clientQueryFactory;

    /**
     * @var DeviceQueryFactory
     */
    protected $deviceQueryFactory;

    /**
     * @var HelpQueryFactory
     */
    protected $helpQueryFactory;

    /**
     * @var InvoiceQueryFactory
     */
    protected $invoiceQueryFactory;

    /**
     * @var PaymentQueryFactory
     */
    protected $paymentQueryFactory;

    /**
     * @var SiteQueryFactory
     */
    protected $siteQueryFactory;

    /**
     * @var TicketQueryFactory
     */
    protected $ticketQueryFactory;

    /**
     * @var QuoteQueryFactory
     */
    protected $quoteQueryFactory;

    /**
     * @var array|BaseQueryFactory[]
     */
    protected $queryFactories;

    /**
     * @var NavigationQueryFactory
     */
    protected $navigationQueryFactory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        iterable $transformers,
        Client $elasticClient,
        IndexManager $indexManager,
        PermissionGrantedChecker $permissionGrantedChecker,
        ClientQueryFactory $clientQueryFactory,
        DeviceQueryFactory $deviceQueryFactory,
        HelpQueryFactory $helpQueryFactory,
        InvoiceQueryFactory $invoiceQueryFactory,
        PaymentQueryFactory $paymentQueryFactory,
        SiteQueryFactory $siteQueryFactory,
        TicketQueryFactory $ticketQueryFactory,
        QuoteQueryFactory $quoteQueryFactory,
        NavigationQueryFactory $navigationQueryFactory,
        TranslatorInterface $translator
    ) {
        /** @var ElasticaToModelTransformerInterface $transformer */
        foreach ($transformers as $transformer) {
            switch ($transformer->getObjectClass()) {
                case Entity\Client::class:
                    $this->transformers[self::TYPE_CLIENT] = $transformer;

                    break;
                case Entity\Device::class:
                    $this->transformers[self::TYPE_DEVICE] = $transformer;

                    break;
                case Entity\Financial\Invoice::class:
                    $this->transformers[self::TYPE_INVOICE] = $transformer;

                    break;
                case Entity\Payment::class:
                    $this->transformers[self::TYPE_PAYMENT] = $transformer;

                    break;
                case Entity\Site::class:
                    $this->transformers[self::TYPE_SITE] = $transformer;

                    break;
                case Ticket::class:
                    $this->transformers[self::TYPE_TICKET] = $transformer;

                    break;
                case Entity\Financial\Quote::class:
                    $this->transformers[self::TYPE_QUOTE] = $transformer;

                    break;
            }
        }

        $this->elasticClient = $elasticClient;
        $this->indexManager = $indexManager;
        $this->permissionGrantedChecker = $permissionGrantedChecker;

        $this->clientQueryFactory = $clientQueryFactory;
        $this->deviceQueryFactory = $deviceQueryFactory;
        $this->helpQueryFactory = $helpQueryFactory;
        $this->invoiceQueryFactory = $invoiceQueryFactory;
        $this->paymentQueryFactory = $paymentQueryFactory;
        $this->siteQueryFactory = $siteQueryFactory;
        $this->ticketQueryFactory = $ticketQueryFactory;
        $this->quoteQueryFactory = $quoteQueryFactory;
        $this->navigationQueryFactory = $navigationQueryFactory;

        $this->queryFactories = [
            self::TYPE_CLIENT => $this->clientQueryFactory,
            self::TYPE_DEVICE => $this->deviceQueryFactory,
            self::TYPE_HELP => $this->helpQueryFactory,
            self::TYPE_INVOICE => $this->invoiceQueryFactory,
            self::TYPE_PAYMENT => $this->paymentQueryFactory,
            self::TYPE_SITE => $this->siteQueryFactory,
            self::TYPE_TICKET => $this->ticketQueryFactory,
            self::TYPE_QUOTE => $this->quoteQueryFactory,
            self::TYPE_NAVIGATION => $this->navigationQueryFactory,
        ];

        $this->translator = $translator;
    }

    /**
     * Used for localized search, the type is base (e.g. navigation)
     * and index is the type with appended language (e.g. navigation_de).
     */
    protected function getLocaleType(string $type): Type
    {
        if ($this->translator instanceof Translator || method_exists($this->translator, 'getFallbackLocales')) {
            $locales = $this->translator->getFallbackLocales();
        } else {
            $locales = [];
        }
        array_unshift($locales, $this->translator->getLocale());

        $exception = null;
        foreach ($locales as $locale) {
            try {
                $elasticType = $this->getType(
                    $type,
                    sprintf(
                        '%s_%s',
                        $type,
                        Strings::lower($locale)
                    )
                );

                return $elasticType;
            } catch (ElasticsearchException $exception) {
            }
        }

        throw ($exception ?? new ElasticsearchException(sprintf('Locale type "%s" does not exist.', $type)));
    }

    protected function getType(string $type, ?string $index = null): Type
    {
        try {
            $elasticIndex = $this->indexManager->getIndex($index ?? $type);
        } catch (\InvalidArgumentException $exception) {
            throw new ElasticsearchException($exception->getMessage());
        }

        if (! $elasticIndex->exists()) {
            throw new ElasticsearchException(
                sprintf('Index "%s" does not exist.', $elasticIndex->getName())
            );
        }

        $elasticType = $elasticIndex->getType($type);
        if (! $elasticType->exists()) {
            throw new ElasticsearchException(
                sprintf('Type "%s" does not exist.', $elasticType->getName())
            );
        }

        return $elasticType;
    }

    /**
     * @todo This needs to be refactored together with DI Resolver.
     */
    protected function isAllowed(string $type): bool
    {
        switch ($type) {
            case self::TYPE_CLIENT:
                return $this->permissionGrantedChecker->isGranted(Permission::VIEW, ClientController::class);
                break;
            case self::TYPE_DEVICE:
                return $this->permissionGrantedChecker->isGranted(Permission::VIEW, DeviceController::class);
                break;
            case self::TYPE_INVOICE:
                return $this->permissionGrantedChecker->isGranted(Permission::VIEW, InvoiceController::class);
                break;
            case self::TYPE_PAYMENT:
                return $this->permissionGrantedChecker->isGranted(Permission::VIEW, PaymentController::class);
                break;
            case self::TYPE_TICKET:
                return $this->permissionGrantedChecker->isGranted(Permission::VIEW, TicketController::class);
                break;
            case self::TYPE_QUOTE:
                return $this->permissionGrantedChecker->isGranted(Permission::VIEW, QuoteController::class);
                break;
            default:
                return true;
        }
    }
}
