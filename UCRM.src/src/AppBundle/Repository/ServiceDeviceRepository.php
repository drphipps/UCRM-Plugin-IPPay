<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Client;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Util\Mac;
use Doctrine\ORM\AbstractQuery;

class ServiceDeviceRepository extends BaseRepository
{
    /**
     * @return array [bool $isKnown, ServiceDevice|null $serviceDevice]
     */
    public function findUnknownByMac(string $mac): array
    {
        $serviceDevice = $this->createQueryBuilder('sd')
            ->where('sd.macAddress = :mac')
            ->orderBy('sd.service', 'ASC')
            ->setMaxResults(1)
            ->setParameter('mac', Mac::format($mac))
            ->getQuery()->getOneOrNullResult();

        return [
            $serviceDevice && null !== $serviceDevice->getService(),
            $serviceDevice,
        ];
    }

    /**
     * @return array|ServiceDevice[]
     */
    public function getDeletedByClient(Client $client): array
    {
        return $this->createQueryBuilder('sd')
            ->addSelect('sdsi, v')
            ->join('sd.service', 's')
            ->join('s.client', 'c')
            ->leftJoin('sd.vendor', 'v')
            ->leftJoin('sd.serviceIps', 'sdsi')
            ->andWhere('s.deletedAt IS NOT NULL')
            ->andWhere('c.id = :client')
            ->setParameter('client', $client)
            ->getQuery()->getResult();
    }

    public function hasClientDeleted(Client $client): bool
    {
        return (bool) $this->createQueryBuilder('sd')
            ->select('sd.id')
            ->join('sd.service', 's')
            ->join('s.client', 'c')
            ->andWhere('s.deletedAt IS NOT NULL')
            ->andWhere('c.id = :client')
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }
}
