<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use AppBundle\Entity\General;
use AppBundle\Facade\InvoiceExportFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ExportInvoicesConsumer extends AbstractConsumer
{
    /**
     * @var InvoiceExportFacade
     */
    private $invoiceExportFacade;

    public function __construct(
        InvoiceExportFacade $invoiceExportFacade,
        EntityManagerInterface $em,
        Options $options,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger, $options);

        $this->invoiceExportFacade = $invoiceExportFacade;
    }

    protected function getMessageClass(): string
    {
        return ExportInvoicesMessage::class;
    }

    public function executeBody(array $data): int
    {
        if ($this->options->getGeneral(General::INVOICE_TOTALS_MIGRATION_COMPLETE) !== '1') {
            sleep(1);

            return self::MSG_REJECT_REQUEUE;
        }

        $status = $this->invoiceExportFacade->finishPdfExport($data['download'], $data['invoices']);

        if ($status) {
            $this->logger->info('Generated invoice export.');
        } else {
            $this->logger->error('Invoice export failed.');
        }

        return $status ? self::MSG_ACK : self::MSG_REJECT;
    }
}
