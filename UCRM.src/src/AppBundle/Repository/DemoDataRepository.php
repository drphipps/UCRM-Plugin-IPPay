<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DemoDataRepository
{
    /**
     * @var int[]
     */
    private $nonLeadServiceIds = [];

    /**
     * @var int[]
     */
    private $nonLeadClientIds = [];

    /**
     * @var int[]
     */
    private $adminIds = [];

    /**
     * @var int[]
     */
    private $nonLeadServiceDeviceIds = [];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return int[]
     */
    public function getNonLeadClientIds(): array
    {
        if ($this->nonLeadClientIds) {
            return $this->nonLeadClientIds;
        }

        $ids = $this->entityManager->getRepository(Client::class)
            ->createQueryBuilder('c')
            ->select('c.id AS id')
            ->where('c.isLead = FALSE')
            ->andWhere('c.deletedAt IS NULL')
            ->getQuery()
            ->getResult();

        $this->nonLeadClientIds = array_column($ids, 'id');

        return $this->nonLeadClientIds;
    }

    /**
     * @return int[]
     */
    public function getAdminIds(): array
    {
        if ($this->adminIds) {
            return $this->adminIds;
        }

        $ids = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u.id AS id')
            ->where('u.role IN (:roles)')
            ->setParameter('roles', User::ADMIN_ROLES)
            ->getQuery()
            ->getResult();

        $this->adminIds = array_column($ids, 'id');

        return $this->adminIds;
    }

    /**
     * @return int[]
     */
    public function getNonLeadServiceIds(): array
    {
        if ($this->nonLeadServiceIds) {
            return $this->nonLeadServiceIds;
        }

        $ids = $this->entityManager->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->select('s.id AS id')
            ->join('s.client', 'c')
            ->where('c.isLead = FALSE')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('c.deletedAt IS NULL')
            ->getQuery()
            ->getResult();

        $this->nonLeadServiceIds = array_column($ids, 'id');

        return $this->nonLeadServiceIds;
    }

    /**
     * @return int[]
     */
    public function getNonLeadServiceDeviceIds(): array
    {
        if ($this->nonLeadServiceDeviceIds) {
            return $this->nonLeadServiceDeviceIds;
        }

        $ids = $this->entityManager->getRepository(ServiceDevice::class)
            ->createQueryBuilder('sd')
            ->select('sd.id AS id')
            ->where('sd.service IN (:ids)')
            ->setParameter('ids', $this->getNonLeadServiceIds())
            ->getQuery()
            ->getResult();

        $this->nonLeadServiceDeviceIds = array_column($ids, 'id');

        return $this->nonLeadServiceDeviceIds;
    }
}
