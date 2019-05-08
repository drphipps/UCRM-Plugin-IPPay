<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\ClientTag;
use Doctrine\ORM\EntityManagerInterface;

class ClientTagFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function handleCreate(ClientTag $clientTag): void
    {
        $this->em->persist($clientTag);
        $this->em->flush();
    }

    public function handleUpdate(ClientTag $clientTag): void
    {
        $this->em->flush();
    }

    public function handleDelete(ClientTag $clientTag): void
    {
        $this->em->remove($clientTag);
        $this->em->flush();
    }
}
