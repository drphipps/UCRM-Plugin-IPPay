<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use AppBundle\Component\Financial\FinancialEmailSender;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SendInvoiceConsumer extends AbstractConsumer
{
    /**
     * @var FinancialEmailSender
     */
    private $financialEmailSender;

    public function __construct(
        EntityManagerInterface $em,
        Options $options,
        LoggerInterface $logger,
        FinancialEmailSender $financialEmailSender
    ) {
        parent::__construct($em, $logger, $options);

        $this->financialEmailSender = $financialEmailSender;
    }

    protected function getMessageClass(): string
    {
        return SendInvoiceMessage::class;
    }

    public function executeBody(array $data): int
    {
        if ($this->options->getGeneral(General::INVOICE_TOTALS_MIGRATION_COMPLETE) !== '1') {
            sleep(1);

            return self::MSG_REJECT_REQUEUE;
        }

        $invoice = $this->entityManager->find(Invoice::class, $data['invoice']);

        if (! $invoice) {
            $this->logger->warning(sprintf('Invoice %d not found.', $data['invoice']));

            return self::MSG_REJECT;
        }

        if ($invoice->getInvoiceStatus() === Invoice::VOID) {
            $this->logger->warning(sprintf('Invoice %d is void. Removing from queue.', $data['invoice']));

            return self::MSG_REJECT;
        }

        if ($invoice->getInvoiceStatus() === Invoice::DRAFT || null === $invoice->getPdfPath()) {
            $this->logger->warning(sprintf('Skipping invoice %d due to incomplete data.', $data['invoice']));

            return self::MSG_REJECT;
        }

        if (
            ! $this->options->get(Option::SEND_INVOICE_WITH_ZERO_BALANCE)
            && round(
                $invoice->getAmountToPay(),
                $invoice->getCurrency() ? $invoice->getCurrency()->getFractionDigits() : 2
            ) <= 0
        ) {
            $this->logger->warning(sprintf('Skipping invoice %d due to zero balance.', $data['invoice']));

            return self::MSG_REJECT;
        }

        $this->financialEmailSender->send(
            $invoice,
            $invoice->isProforma()
                ? NotificationTemplate::CLIENT_NEW_PROFORMA_INVOICE
                : NotificationTemplate::CLIENT_NEW_INVOICE
        );
        $this->logger->info(sprintf('Added invoice %d to the send queue.', $data['invoice']));

        return self::MSG_ACK;
    }
}
