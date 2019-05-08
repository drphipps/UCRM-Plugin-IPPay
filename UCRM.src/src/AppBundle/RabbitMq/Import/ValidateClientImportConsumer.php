<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Import;

use AppBundle\Component\Import\Facade\ClientImportPreviewFacade;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ValidateClientImportConsumer extends AbstractConsumer
{
    /**
     * @var ClientImportPreviewFacade
     */
    private $clientImportPreviewFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Options $options,
        ClientImportPreviewFacade $clientImportPreviewFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->clientImportPreviewFacade = $clientImportPreviewFacade;
    }

    protected function getMessageClass(): string
    {
        return ValidateClientImportMessage::class;
    }

    public function executeBody(array $data): int
    {
        $clientImport = $this->entityManager->find(ClientImport::class, $data['importId']);
        if (! $clientImport) {
            $this->logger->warning(sprintf('Client import "%s" not found.', $data['importId']));

            return self::MSG_REJECT;
        }

        if ($clientImport->getStatus() !== ImportInterface::STATUS_ITEMS_LOADED) {
            $this->logger->warning('Items can be validated only for import with "status = STATUS_ITEMS_LOADED".');

            return self::MSG_REJECT;
        }

        $this->logger->info(sprintf('Started validation of import "%s".', $clientImport->getId()));

        $this->clientImportPreviewFacade->validateItems($clientImport);

        $this->logger->info(sprintf('Finished validation of import "%s".', $clientImport->getId()));

        // clear memory
        $this->entityManager->clear();

        return self::MSG_ACK;
    }
}
