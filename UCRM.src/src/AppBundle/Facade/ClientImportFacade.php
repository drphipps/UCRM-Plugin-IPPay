<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Event\Client\ClientAddEvent;
use AppBundle\Event\Client\ClientAddImportEvent;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceAddImportEvent;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class ClientImportFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(TransactionDispatcher $transactionDispatcher)
    {
        $this->transactionDispatcher = $transactionDispatcher;
    }

    /**
     * @param Client[]  $clients
     * @param Service[] $services
     *
     * @throws \Throwable
     */
    public function handleCreateFromCsvImport(array $clients, array $services): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($clients, $services) {
                foreach ($clients as $client) {
                    $entityManager->persist($client);

                    yield new ClientAddEvent($client);
                    yield new ClientAddImportEvent($client);
                }

                foreach ($services as $service) {
                    $entityManager->persist($service);

                    yield new ServiceAddEvent($service);
                    yield new ServiceAddImportEvent($service);
                }
            },
            Connection::TRANSACTION_REPEATABLE_READ
        );
    }
}
