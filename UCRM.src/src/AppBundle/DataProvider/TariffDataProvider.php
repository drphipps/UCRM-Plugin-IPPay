<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;

class TariffDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getActiveTariffNamesForForm(): array
    {
        $tariffs = $this->em->getRepository(Tariff::class)
            ->createQueryBuilder('t', 't.id')
            ->leftJoin('t.services', 's', Expr\Join::WITH, 's.status IN (:statuses)')
            ->leftJoin('t.organization', 'o')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter(
                'statuses',
                [
                    Service::STATUS_ACTIVE,
                    Service::STATUS_SUSPENDED,
                    Service::STATUS_PREPARED,
                    Service::STATUS_PREPARED_BLOCKED,
                ]
            )
            ->groupBy('t.id, o.name, s.name')
            ->orderBy('o.name, s.name')
            ->getQuery()
            ->getResult();

        $organizationTariffs = [];
        /** @var Tariff $tariff */
        foreach ($tariffs as $tariff) {
            if ($organization = $tariff->getOrganization()) {
                if (! array_key_exists($organization->getId(), $organizationTariffs)) {
                    $organizationTariffs[$organization->getId()] = [
                        'label' => $organization->getName(),
                        'items' => [],
                    ];
                }

                $organizationTariffs[$organization->getId()]['items'][$tariff->getId()] = $tariff->getName();
            }
        }

        return $organizationTariffs;
    }
}
