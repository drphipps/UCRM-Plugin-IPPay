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

class ExportQuotesConsumer extends AbstractConsumer
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
        return ExportQuotesMessage::class;
    }

    public function executeBody(array $data): int
    {
        $status = $this->quoteExportFacade->finishPdfExport($data['download'], $data['quotes']);

        if ($status) {
            $this->logger->info('Generated quote export.');
        } else {
            $this->logger->error('Quote export failed.');
        }

        return $status ? self::MSG_ACK : self::MSG_REJECT;
    }
}
