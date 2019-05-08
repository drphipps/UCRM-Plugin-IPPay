<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\NetflowExcludedIp;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class NetflowExcludedIpFacade implements GridFacadeInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->em->getRepository(NetflowExcludedIp::class)->createQueryBuilder('nei');
    }

    public function handleNew(NetflowExcludedIp $netflowExcludedIp): void
    {
        $this->em->persist($netflowExcludedIp);
        $this->em->flush();
    }

    public function handleDelete(NetflowExcludedIp $netflowExcludedIp): void
    {
        $this->em->remove($netflowExcludedIp);
        $this->em->flush();
    }

    public function handleDeleteMultiple(array $ids): ?int
    {
        $excludedIps = $this->em->getRepository(NetflowExcludedIp::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        foreach ($excludedIps as $excludedIp) {
            $this->em->remove($excludedIp);
        }

        $this->em->flush();

        return count($excludedIps);
    }

    public function handleEdit(NetflowExcludedIp $netflowExcludedIp): void
    {
        $this->em->flush();
    }
}
