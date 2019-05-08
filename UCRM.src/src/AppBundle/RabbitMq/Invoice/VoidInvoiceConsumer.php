<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Facade\InvoiceFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class VoidInvoiceConsumer extends AbstractConsumer
{
    /**
     * @var InvoiceFacade
     */
    private $invoiceFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        LoggerInterface $logger,
        InvoiceFacade $invoiceFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->invoiceFacade = $invoiceFacade;
    }

    protected function getMessageClass(): string
    {
        return VoidInvoiceMessage::class;
    }

    public function executeBody(array $data): int
    {
        if ($this->options->getGeneral(General::INVOICE_TOTALS_MIGRATION_COMPLETE) !== '1') {
            sleep(1);

            return self::MSG_REJECT_REQUEUE;
        }

        $invoice = $this->entityManager->find(Invoice::class, $data['invoiceId']);

        if (! $invoice) {
            $this->logger->warning(sprintf('Invoice %d not found.', $data['invoiceId']));

            return self::MSG_REJECT;
        }

        if (! $this->invoiceFacade->handleVoid($invoice)) {
            $this->logger->warning(sprintf('Could not void invoice %d.', $data['invoiceId']));

            return self::MSG_REJECT;
        }

        $this->logger->info(sprintf('Invoice %d voided.', $data['invoiceId']));

        return self::MSG_ACK;
    }
}
