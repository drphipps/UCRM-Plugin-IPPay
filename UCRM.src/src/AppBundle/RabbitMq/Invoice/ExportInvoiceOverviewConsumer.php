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

class ExportInvoiceOverviewConsumer extends AbstractConsumer
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
        return ExportInvoiceOverviewMessage::class;
    }

    public function executeBody(array $data): int
    {
        if ($this->options->getGeneral(General::INVOICE_TOTALS_MIGRATION_COMPLETE) !== '1') {
            sleep(1);

            return self::MSG_REJECT_REQUEUE;
        }

        switch ($data['format']) {
            case ExportInvoiceOverviewMessage::FORMAT_PDF:
                $status = $this->invoiceExportFacade->finishPdfOverviewExport($data['download'], $data['invoices']);
                break;
            case ExportInvoiceOverviewMessage::FORMAT_CSV:
                $status = $this->invoiceExportFacade->finishCsvOverviewExport($data['download'], $data['invoices']);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Export format ("%s") not supported.', $data['format']));
        }

        if ($status) {
            $this->logger->info(sprintf('Generated invoice overview export (format "%s").', $data['format']));
        } else {
            $this->logger->error(sprintf('Invoice overview export failed (format "%s").', $data['format']));
        }

        return $status ? self::MSG_ACK : self::MSG_REJECT;
    }
}
