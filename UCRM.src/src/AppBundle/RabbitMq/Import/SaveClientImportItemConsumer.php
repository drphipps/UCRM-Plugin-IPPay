<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Import;

use AppBundle\Component\Import\Facade\ClientImportSaveFacade;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SaveClientImportItemConsumer extends AbstractConsumer
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
        return SaveClientImportItemMessage::class;
    }

    public function executeBody(array $data): int
    {
        $clientImportItem = $this->entityManager->find(ClientImportItem::class, $data['itemId']);
        if (! $clientImportItem) {
            $this->logger->warning(sprintf('Client import item "%s" not found.', $data['itemId']));

            return self::MSG_REJECT;
        }

        if (! $clientImportItem->getImport()->isStatusDone(ImportInterface::STATUS_ITEMS_ENQUEUEING)) {
            $this->logger->warning(
                'Items can be saved only for import with at least "status = STATUS_ITEMS_ENQUEUEING".'
            );

            return self::MSG_REJECT;
        }

        $this->logger->info(sprintf('Started saving import item "%s".', $clientImportItem->getId()));

        $this->clientImportSaveFacade->saveItem($clientImportItem);

        $this->logger->info(sprintf('Finished saving import item "%s".', $clientImportItem->getId()));

        // clear memory
        $this->entityManager->clear();

        return self::MSG_ACK;
    }
}
