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
use RabbitMqBundle\RabbitMqEnqueuer;

class LoadClientImportConsumer extends AbstractConsumer
{
    /**
     * @var ClientImportPreviewFacade
     */
    private $clientImportPreviewFacade;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Options $options,
        ClientImportPreviewFacade $clientImportPreviewFacade,
        RabbitMqEnqueuer $rabbitMqEnqueuer
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->clientImportPreviewFacade = $clientImportPreviewFacade;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
    }

    protected function getMessageClass(): string
    {
        return LoadClientImportMessage::class;
    }

    public function executeBody(array $data): int
    {
        $clientImport = $this->entityManager->find(ClientImport::class, $data['importId']);
        if (! $clientImport) {
            $this->logger->warning(sprintf('Client import "%s" not found.', $data['importId']));

            return self::MSG_REJECT;
        }

        if ($clientImport->getStatus() !== ImportInterface::STATUS_MAPPED) {
            $this->logger->warning('Items can be loaded only for import with "status = STATUS_MAPPED".');

            return self::MSG_REJECT;
        }

        $this->logger->info(sprintf('Started loading items of import "%s".', $clientImport->getId()));
        $this->clientImportPreviewFacade->loadItemsToDatabase($clientImport);
        $this->logger->info(sprintf('Finished loading items of import "%s".', $clientImport->getId()));

        $this->logger->info(sprintf('Enqueuing validation of import "%s".', $clientImport->getId()));
        $this->rabbitMqEnqueuer->enqueue(new ValidateClientImportMessage($clientImport->getId()));
        $this->logger->info(sprintf('Enqueued validation of import "%s".', $clientImport->getId()));

        // clear memory
        $this->entityManager->clear();

        return self::MSG_ACK;
    }
}
