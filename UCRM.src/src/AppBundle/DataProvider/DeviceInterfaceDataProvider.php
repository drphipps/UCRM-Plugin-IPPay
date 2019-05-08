<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class DeviceInterfaceDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getAllDeviceInterfaces(Device $device): array
    {
        $repository = $this->entityManager->getRepository(DeviceInterface::class);
        $deviceInterfaces = $repository->findBy(
            [
                'device' => $device,
                'deletedAt' => null,
            ],
            [
                'id' => 'ASC',
            ]
        );

        return $deviceInterfaces;
    }

    public function getGridModel(Device $device): QueryBuilder
    {
        return $this->entityManager->getRepository(DeviceInterface::class)
            ->createQueryBuilder('di')
            ->addSelect(
                'string_agg_multi_distinct(dii.ipRange.ipAddress, dii.ipRange.netmask, \'/\', \',\') AS ipAddresses'
            )
            ->leftJoin('di.interfaceIps', 'dii')
            ->andWhere('di.deletedAt IS NULL')
            ->andWhere('di.device = :deviceId')
            ->groupBy('di.id')
            ->setParameter('deviceId', $device->getId());
    }
}
