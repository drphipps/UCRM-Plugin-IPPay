<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class ClientContactFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(TransactionDispatcher $transactionDispatcher)
    {
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function setDefaults(Client $client, ClientContact $clientContact): void
    {
        $clientContact->setClient($client);
        $client->addContact($clientContact);
    }

    public function handleCreate(ClientContact $clientContact): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($clientContact) {
                $entityManager->persist($clientContact);
            }
        );
    }

    public function handleDelete(ClientContact $clientContact): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($clientContact) {
                $entityManager->remove($clientContact);
            }
        );
    }

    public function handleUpdate(ClientContact $clientContact)
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($clientContact) {
                $entityManager->persist($clientContact);
            }
        );
    }
}
