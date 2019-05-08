<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Client;

use AppBundle\Facade\ClientFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ExportClientsConsumer extends AbstractConsumer
{
    /**
     * @var ClientFacade
     */
    private $clientFacade;

    public function __construct(
        ClientFacade $clientFacade,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Options $options
    ) {
        parent::__construct($em, $logger, $options);

        $this->clientFacade = $clientFacade;
    }

    protected function getMessageClass(): string
    {
        return ExportClientsMessage::class;
    }

    public function executeBody(array $data): int
    {
        switch ($data['format']) {
            case ExportClientsMessage::FORMAT_CSV:
                $status = $this->clientFacade->finishCsvExport($data['download'], $data['ids']);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Export format ("%s") not supported.', $data['format']));
        }

        if ($status) {
            $this->logger->info(sprintf('Generated client export (format "%s").', $data['format']));
        } else {
            $this->logger->error(sprintf('Client export failed (format "%s").', $data['format']));
        }

        return $status ? self::MSG_ACK : self::MSG_REJECT;
    }
}
