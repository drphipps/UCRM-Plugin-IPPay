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
use AppBundle\Event\Invoice\InvoiceOverdueEvent;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Repository\InvoiceRepository;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class OverdueNotifier
{
    private const BULK_COUNT = 20;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FinancialEmailSender
     */
    private $financialEmailSender;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

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
        if (! $this->options->get(Option::NOTIFICATION_INVOICE_OVERDUE)) {
            $this->logger->info('Overdue invoice notifications are disabled.');

            return;
        }

        $this->logger->info('Sending invoice overdue notifications.');

        $countSuccess = 0;
        $countFail = 0;
        do {
            $invoices = $this->invoiceRepository->getOverdueInvoicesForNotifications(self::BULK_COUNT);

            foreach ($invoices as $invoice) {
                try {
                    $this->financialEmailSender->send($invoice, NotificationTemplate::CLIENT_OVERDUE_INVOICE);
                    $this->transactionDispatcher->transactional(
                        function () use ($invoice) {
                            yield new InvoiceOverdueEvent($invoice);
                        }
                    );
                    $invoice->setOverdueNotificationSent(true);
                    ++$countSuccess;
                } catch (TemplateRenderException $exception) {
                    ++$countFail;
                }
            }

            $this->em->flush();
            $this->em->clear();
        } while (count($invoices) === self::BULK_COUNT);

        $this->logger->info(sprintf('System added %d overdue notifications to the send queue.', $countSuccess));
        if ($countFail) {
            $this->logger->error(
                sprintf(
                    'The system did not add %d overdue notifications to the send queue because the invoice has an invalid template.',
                    $countFail
                )
            );
        }
    }
}
