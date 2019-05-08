<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\ValidationHttpException;
use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\QuoteMap;
use ApiBundle\Mapper\QuoteMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\QuoteController as AppQuoteController;
use AppBundle\DataProvider\QuoteDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\QuoteFacade;
use AppBundle\Factory\Financial\FinancialFactory;
use AppBundle\Request\QuoteCollectionRequest;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppQuoteController::class)
 */
class QuoteController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var QuoteFacade
     */
    private $facade;

    /**
     * @var QuoteMapper
     */
    private $mapper;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var FinancialFactory
     */
    private $financialFactory;

    /**
     * @var QuoteDataProvider
     */
    private $dataProvider;

    public function __construct(
        Validator $validator,
        QuoteFacade $facade,
        QuoteMapper $mapper,
        EntityManager $em,
        FinancialFactory $financialFactory,
        QuoteDataProvider $dataProvider
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->em = $em;
        $this->financialFactory = $financialFactory;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get("/quotes/{id}", name="quote_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @Get("/clients/quotes/{id}", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Quote $quote): View
    {
        return $this->view(
            $this->mapper->reflect($quote)
        );
    }

    /**
     * @Get(
     *     "/quotes",
     *     name="quote_collection_get",
     *     options={"method_prefix"=false},
     * )
     * @ViewHandler()
     * @Permission("view")
     * @QueryParam(
     *     name="createdDateFrom",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection starting on date (including)"
     * )
     * @QueryParam(
     *     name="createdDateTo",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection ending on date (including)"
     * )
     * @QueryParam(
     *     name="clientId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="client ID"
     * )
     * @QueryParam(
     *     name="number",
     *     nullable=true,
     *     description="search by quote number"
     * )
     * @QueryParam(
     *     name="statuses",
     *     requirements=@Assert\All(@Assert\Choice(Quote::STATUSES)),
     *     strict=true,
     *     nullable=true,
     *     description="select only quotes in one of the given statuses"
     * )
     * @QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="max results limit"
     * )
     * @QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="results offset"
     * )
     * @QueryParam(
     *     name="order",
     *     requirements="clientFirstName|clientLastName|createdDate",
     *     strict=true,
     *     nullable=true,
     *     description="order by (clientFirstName|clientLastName|createdDate)"
     * )
     * @QueryParam(
     *     name="direction",
     *     requirements="ASC|DESC",
     *     strict=true,
     *     nullable=true,
     *     description="direction of sort - ascending (ASC) or descending (DESC)"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        if ($clientId = $paramFetcher->get('clientId')) {
            $client = $this->em->getRepository(Client::class)->find($clientId);
            if (! $client) {
                throw new NotFoundHttpException('Client object not found.');
            }

            if ($client->isDeleted()) {
                throw new NotFoundHttpException('Client is archived. All actions are prohibited. You can only restore the client.');
            }
        }

        if ($startDate = $paramFetcher->get('createdDateFrom')) {
            try {
                $startDate = DateTimeFactory::createDate($startDate);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }
        if ($endDate = $paramFetcher->get('createdDateTo')) {
            try {
                $endDate = DateTimeFactory::createDate($endDate);
                $endDate->setTime(23, 59, 59);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }

        $statuses = $paramFetcher->get('statuses');
        if ($statuses) {
            $statuses = Helpers::typeCastAll('int', $statuses);
        }

        $request = new QuoteCollectionRequest();
        $request->client = $client ?? null;
        $request->startDate = $startDate;
        $request->endDate = $endDate;
        $request->statuses = $statuses;
        $request->number = $paramFetcher->get('number', true);
        $request->limit = Helpers::typeCastNullable('int', $paramFetcher->get('limit'));
        $request->offset = Helpers::typeCastNullable('int', $paramFetcher->get('offset'));
        $request->order = $paramFetcher->get('order', true);
        $request->direction = $paramFetcher->get('direction', true);

        $quotes = $this->dataProvider->getQuotes($request);

        return $this->view(
            $this->mapper->reflectCollection($quotes)
        );
    }

    /**
     * @Post("/clients/{id}/quotes", name="client_quote_add", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("quoteMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(Client $client, QuoteMap $quoteMap, string $version): View
    {
        if ($client->isDeleted()) {
            throw new NotFoundHttpException('Client is archived. All actions are prohibited. You can only restore the client.');
        }

        $quote = $this->financialFactory->createQuote($client, new \DateTimeImmutable());
        $this->mapper->map($quoteMap, $quote);

        $validationGroups = [FinancialInterface::VALIDATION_GROUP_DEFAULT, FinancialInterface::VALIDATION_GROUP_API];
        $this->validator->validate($quote, $this->mapper->getFieldsDifference(), null, $validationGroups);

        try {
            $this->facade->handleQuoteCreateAPI($quote);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            throw new ValidationHttpException([], 'Quote template contains errors and can\'t be safely used.');
        }

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($quote),
            'api_quote_get',
            [
                'version' => $version,
                'id' => $quote->getId(),
            ]
        );
    }

    /**
     * @Patch("/quotes/{id}", name="quote_edit", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("quoteMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(Quote $quote, QuoteMap $quoteMap): View
    {
        if ($quote->getClient()->isDeleted()) {
            throw new NotFoundHttpException('Client is archived. All actions are prohibited. You can only restore the client.');
        }

        $this->mapper->map($quoteMap, $quote);
        $validationGroups = [FinancialInterface::VALIDATION_GROUP_DEFAULT, FinancialInterface::VALIDATION_GROUP_API];
        $this->validator->validate($quote, $this->mapper->getFieldsDifference(), null, $validationGroups);

        try {
            $this->facade->handleQuoteUpdateAPI($quote);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            throw new ValidationHttpException([], 'Quote template contains errors and can\'t be safely used.');
        }

        return $this->view(
            $this->mapper->reflect($quote)
        );
    }
}
