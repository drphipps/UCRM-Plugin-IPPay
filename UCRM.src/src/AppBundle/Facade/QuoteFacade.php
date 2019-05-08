<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItemFee;
use AppBundle\Entity\Financial\QuoteItemService;
use AppBundle\Entity\Service;
use AppBundle\Event\Quote\QuoteAddEvent;
use AppBundle\Event\Quote\QuoteDeleteEvent;
use AppBundle\Event\Quote\QuoteEditEvent;
use AppBundle\Event\Service\ServiceActivateEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Handler\Quote\PdfHandler;
use AppBundle\Service\Financial\FinancialTotalCalculator;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class QuoteFacade
{
    use QuoteServiceActionsTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var FinancialTotalCalculator
     */
    private $financialTotalCalculator;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        TransactionDispatcher $transactionDispatcher,
        FinancialTotalCalculator $financialTotalCalculator,
        PdfHandler $pdfHandler
    ) {
        $this->entityManager = $entityManager;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->financialTotalCalculator = $financialTotalCalculator;
        $this->pdfHandler = $pdfHandler;
    }

    public function handleAccept(Quote $quote): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($quote) {
                $quoteBeforeEdit = clone $quote;
                $quote->setStatus(Quote::STATUS_ACCEPTED);
                yield new QuoteEditEvent($quote, $quoteBeforeEdit);

                $entityManager->getRepository(Quote::class)->loadRelatedEntities('quoteItems', [$quote->getId()]);
                yield from $this->activateQuotedServices($quote);
            }
        );
    }

    public function handleReject(Quote $quote): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($quote) {
                $quoteBeforeEdit = clone $quote;
                $quote->setStatus(Quote::STATUS_REJECTED);
                yield new QuoteEditEvent($quote, $quoteBeforeEdit);
            }
        );
    }

    public function handleReopen(Quote $quote): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($quote) {
                $quoteBeforeEdit = clone $quote;
                $quote->setStatus(Quote::STATUS_OPEN);
                yield new QuoteEditEvent($quote, $quoteBeforeEdit);
            }
        );
    }

    public function handleDelete(Quote $quote): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($quote) {
                $quoteId = $quote->getId();
                $this->entityManager->remove($quote);
                yield new QuoteDeleteEvent($quote, $quoteId);
            }
        );
    }

    /**
     * @param int[] $ids
     *
     * @return array [$deleted, $failed]
     */
    public function handleDeleteMultipleIds(array $ids): array
    {
        $quotes = $this->entityManager->getRepository(Quote::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        return $this->handleDeleteMultiple($quotes);
    }

    /**
     * @param Quote[] $quotes
     *
     * @return array [$deleted, $failed]
     */
    public function handleDeleteMultiple(array $quotes): array
    {
        $count = count($quotes);
        $deleted = 0;

        $this->transactionDispatcher->transactional(
            function () use ($quotes, &$deleted) {
                foreach ($quotes as $quote) {
                    $quoteId = $quote->getId();
                    $this->entityManager->remove($quote);
                    yield new QuoteDeleteEvent($quote, $quoteId);

                    ++$deleted;
                }
            }
        );

        return [$deleted, $count - $deleted];
    }

    /**
     * Used when deleting service and choosing not to keep related quotes.
     *
     * @return array [$deleted, $failed]
     */
    public function handleDeleteMultipleByService(Service $service): array
    {
        $quotes = $this->entityManager->getRepository(Quote::class)
            ->getServiceQuotes($service);

        $cantDelete = 0;
        foreach ($quotes as $key => $quote) {
            foreach ($quote->getQuoteItems() as $item) {
                if ($item instanceof QuoteItemService && $item->getService() === $service) {
                    continue;
                }

                if ($item instanceof QuoteItemFee && $item->getFee() && $item->getFee()->getService() === $service) {
                    continue;
                }

                // Skip quotes with items related to other services.
                ++$cantDelete;
                unset($quotes[$key]);
                break;
            }
        }
        [$deleted, $failed] = $this->handleDeleteMultiple($quotes);

        return [$deleted, $failed + $cantDelete];
    }

    private function activateQuotedServices(Quote $quote): \Generator
    {
        foreach ($quote->getQuoteItems() as $item) {
            if (! $item instanceof QuoteItemService) {
                continue;
            }

            $service = $item->getService();
            $oldService = clone $service;
            if ($this->activateQuotedService($service)) {
                yield new ServiceEditEvent($service, $oldService);
                yield new ServiceActivateEvent($service, $oldService);
            }
        }
    }

    /**
     * @todo For now this method has limited functionality and should be used in API only
     */
    public function handleQuoteCreateAPI(Quote $quote): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($quote) {
                if ($quote->getDiscountValue() > 0) {
                    $quote->setDiscountType(FinancialInterface::DISCOUNT_PERCENTAGE);
                } else {
                    $quote->setDiscountType(FinancialInterface::DISCOUNT_NONE);
                }

                $this->financialTotalCalculator->computeTotal($quote);

                $this->pdfHandler->saveQuotePdf($quote);

                $this->entityManager->persist($quote);

                yield new QuoteAddEvent($quote);
            }
        );
    }

    /**
     * @todo For now this method has limited functionality and should be used in API only
     */
    public function handleQuoteUpdateAPI(Quote $quote): void
    {
        $quoteBeforeUpdate = clone $quote;

        $this->transactionDispatcher->transactional(
            function () use ($quote, $quoteBeforeUpdate) {
                $this->financialTotalCalculator->computeTotal($quote);

                $this->pdfHandler->saveQuotePdf($quote);

                yield new QuoteEditEvent($quote, $quoteBeforeUpdate);
            }
        );
    }
}
