<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\ClientLogsView;

use AppBundle\Facade\ClientLogsViewFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ExportClientLogsViewConsumer extends AbstractConsumer
{
    /**
     * @var ClientLogsViewFacade
     */
    private $clientLogsFacade;

    public function __construct(
        ClientLogsViewFacade $clientLogsFacade,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Options $options
    ) {
        parent::__construct($em, $logger, $options);

        $this->clientLogsFacade = $clientLogsFacade;
    }

    protected function getMessageClass(): string
    {
        return ExportClientLogsViewMessage::class;
    }

    public function executeBody(array $data): int
    {
        switch ($data['format']) {
            case ExportClientLogsViewMessage::FORMAT_PDF:
                $status = $this->clientLogsFacade->finishPdfExport($data['download'], $data['client_logs']);
                break;
            case ExportClientLogsViewMessage::FORMAT_CSV:
                $status = $this->clientLogsFacade->finishCsvExport($data['download'], $data['client_logs']);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Export format ("%s") not supported.', $data['format']));
        }

        if ($status) {
            $this->logger->info(sprintf('Generated client logs view export (format "%s").', $data['format']));
        } else {
            $this->logger->error(sprintf('Client logs view export failed (format "%s").', $data['format']));
        }

        return $status ? self::MSG_ACK : self::MSG_REJECT;
    }
}
