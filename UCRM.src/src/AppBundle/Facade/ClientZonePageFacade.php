<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\ClientZonePage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class ClientZonePageFacade implements GridFacadeInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(ClientZonePage::class)
            ->createQueryBuilder('page')
            ->addSelect('page.position AS page_position');
    }

    public function handleCreate(ClientZonePage $clientZonePage): void
    {
        $clientZonePage->setPosition(-1);

        $this->entityManager->persist($clientZonePage);
        $this->entityManager->flush();
    }

    public function handleDelete(ClientZonePage $clientZonePage): void
    {
        $this->entityManager->remove($clientZonePage);
        $this->entityManager->flush();
    }

    public function handleDeleteMultiple(array $ids): ?int
    {
        $clientZonePages = $this->entityManager->getRepository(ClientZonePage::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        foreach ($clientZonePages as $clientZonePage) {
            $this->entityManager->remove($clientZonePage);
        }

        $this->entityManager->flush();

        return count($clientZonePages);
    }

    public function handleUpdate(ClientZonePage $clientZonePage): void
    {
        $this->entityManager->flush();
    }

    public function handlePositionDown(ClientZonePage $clientZonePage): void
    {
        $maxPosition = $this->entityManager->getRepository(ClientZonePage::class)->getMaxPosition();

        if ($clientZonePage->getPosition() < $maxPosition) {
            $clientZonePage->setPosition($clientZonePage->getPosition() + 1);

            $this->entityManager->flush();
        }
    }

    public function handlePositionUp(ClientZonePage $clientZonePage): void
    {
        if ($clientZonePage->getPosition() > 0) {
            $clientZonePage->setPosition($clientZonePage->getPosition() - 1);

            $this->entityManager->flush();
        }
    }
}
