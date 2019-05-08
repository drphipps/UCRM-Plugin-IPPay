<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Import;

use AppBundle\Component\Import\Facade\ClientImportSaveFacade;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SaveClientImportConsumer extends AbstractConsumer
{
    /**
     * @var ClientImportSaveFacade
     */
    private $clientImportSaveFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Options $options,
        ClientImportSaveFacade $clientImportSaveFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->clientImportSaveFacade = $clientImportSaveFacade;
    }

    protected function getMessageClass(): string
    {
        return SaveClientImportMessage::class;
    }

    public function executeBody(array $data): int
    {
        $clientImport = $this->entityManager->find(ClientImport::class, $data['importId']);
        if (! $clientImport) {
            $this->logger->warning(sprintf('Client import "%s" not found.', $data['importId']));

            return self::MSG_REJECT;
        }

        if ($clientImport->getStatus() !== ImportInterface::STATUS_ENQUEUED) {
            $this->logger->warning('Items can be saved only for import with "status = STATUS_ENQUEUED".');

            return self::MSG_REJECT;
        }

        $this->logger->info(sprintf('Started enqueuing items for import "%s".', $clientImport->getId()));

        $this->clientImportSaveFacade->enqueueSaveItems($clientImport);

        $this->logger->info(sprintf('Finished enqueuing items for import "%s".', $clientImport->getId()));

        // clear memory
        $this->entityManager->clear();

        return self::MSG_ACK;
    }
}
