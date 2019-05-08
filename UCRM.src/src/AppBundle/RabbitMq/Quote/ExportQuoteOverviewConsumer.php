<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Quote;

use AppBundle\Facade\QuoteExportFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ExportQuoteOverviewConsumer extends AbstractConsumer
{
    /**
     * @var QuoteExportFacade
     */
    private $quoteExportFacade;

    public function __construct(
        EntityManagerInterface $em,
        Options $options,
        LoggerInterface $logger,
        QuoteExportFacade $quoteExportFacade
    ) {
        parent::__construct($em, $logger, $options);

        $this->quoteExportFacade = $quoteExportFacade;
    }

    protected function getMessageClass(): string
    {
        return ExportQuoteOverviewMessage::class;
    }

    public function executeBody(array $data): int
    {
        switch ($data['format']) {
            case ExportQuoteOverviewMessage::FORMAT_PDF:
                $status = $this->quoteExportFacade->finishPdfOverviewExport($data['download'], $data['quotes']);
                break;
            case ExportQuoteOverviewMessage::FORMAT_CSV:
                $status = $this->quoteExportFacade->finishCsvOverviewExport($data['download'], $data['quotes']);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Export format ("%s") not supported.', $data['format']));
        }

        if ($status) {
            $this->logger->info(sprintf('Generated quote overview export (format "%s").', $data['format']));
        } else {
            $this->logger->error(sprintf('Quote overview export failed (format "%s").', $data['format']));
        }

        return $status ? self::MSG_ACK : self::MSG_REJECT;
    }
}
