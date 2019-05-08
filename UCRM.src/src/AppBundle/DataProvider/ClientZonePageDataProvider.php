<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\ClientZonePage;
use Doctrine\ORM\EntityManagerInterface;

class ClientZonePageDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return ClientZonePage[]
     */
    public function getPublic(): array
    {
        return  $this->entityManager->getRepository(ClientZonePage::class)->findBy(
            [
                'public' => true,
            ],
            [
                'position' => 'ASC',
            ]
        );
    }
}
