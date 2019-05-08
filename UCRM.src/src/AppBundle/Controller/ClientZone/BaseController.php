<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller\ClientZone;

use AppBundle\Entity\Client;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BaseController extends \AppBundle\Controller\BaseController
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    protected function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @throws AccessDeniedException
     */
    protected function getClient(): Client
    {
        if (! $this->client) {
            $client = $this->getUser() ? $this->em->getRepository(Client::class)->findOneBy(
                [
                    'user' => $this->getUser()->getId(),
                ]
            ) : null;

            if (! $client || $client->isDeleted()) {
                throw $this->createAccessDeniedException();
            }

            $this->setClient($client);
        }

        return $this->client;
    }

    protected function verifyOwnership($item)
    {
        if ($item->getClient() === null || ($item->getClient()->getId() !== $this->getClient()->getId())) {
            throw $this->createAccessDeniedException();
        }
    }
}
