<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\ClientImport;

use AppBundle\Entity\CsvImport;
use AppBundle\Entity\Organization;
use AppBundle\Handler\CsvImport\ClientCsvImportHandler;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ClientImportConsumer extends AbstractConsumer
{
    /**
     * @var ClientCsvImportHandler
     */
    private $clientCsvImportHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Options $options,
        ClientCsvImportHandler $clientCsvImportHandler
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->clientCsvImportHandler = $clientCsvImportHandler;
    }

    protected function getMessageClass(): string
    {
        return ClientImportMessage::class;
    }

    protected function executeBody(array $data): int
    {
        $organization = $this->entityManager->find(Organization::class, (int) $data['organizationId']);
        if (! $organization) {
            $this->logger->warning(sprintf('Organization ID %d not found.', $data['organizationId']));

            return self::MSG_REJECT;
        }

        $csvImport = $this->entityManager->getRepository(CsvImport::class)->findOneBy(
            [
                'uuid' => $data['csvImportUuid'],
            ]
        );
        if (! $csvImport) {
            $this->logger->warning(sprintf('CSV import UUID %s not found.', $data['csvImportUuid']));

            return self::MSG_REJECT;
        }

        $this->clientCsvImportHandler->processClientImport($data['data'], $organization, $csvImport);

        return self::MSG_ACK;
    }
}
