<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Invoice;

use AppBundle\Component\Financial\FinancialEmailSender;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Event\Invoice\InvoiceNearDueEvent;
use AppBundle\Repository\InvoiceRepository;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class NearDueNotifier
{
    private const BULK_COUNT = 20;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FinancialEmailSender
     */
    protected $financialEmailSender;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @var TransactionDispatcher
     */
    protected $transactionDispatcher;

    public function __construct(
        EntityManager $em,
        LoggerInterface $logger,
        FinancialEmailSender $financialEmailSender,
        Options $options,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->financialEmailSender = $financialEmailSender;
        $this->options = $options;
        $this->transactionDispatcher = $transactionDispatcher;

        $this->invoiceRepository = $this->em->getRepository(Invoice::class);
    }

    public function send(): void
    {
        if (! $this->options->get(Option::NOTIFICATION_INVOICE_NEAR_DUE)) {
            $this->logger->info('Near due invoice notifications are disabled.');

            return;
        }

        $this->logger->info('Sending notifications for invoices near due date.');

        $count = 0;

        do {
            $invoices = $this->invoiceRepository->getNearDueInvoicesForNotifications(
                self::BULK_COUNT,
                $this->options->get(Option::NOTIFICATION_INVOICE_NEAR_DUE_DAYS)
            );

            foreach ($invoices as $invoice) {
                $this->financialEmailSender->send($invoice, NotificationTemplate::CLIENT_NEAR_DUE_INVOICE);
                $this->transactionDispatcher->transactional(
                    function () use ($invoice) {
                        yield new InvoiceNearDueEvent($invoice);
                    }
                );

                $invoice->setNearDueNotificationSent(true);
                ++$count;
            }

            $this->em->flush();
            $this->em->clear();
        } while (count($invoices) === self::BULK_COUNT);

        $this->logger->info(sprintf('System added %d near due notifications to the send queue.', $count));
    }
}
