<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\IpAccounting;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class IpAccountingFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Options
     */
    private $options;

    public function __construct(EntityManager $em, Options $options)
    {
        $this->em = $em;
        $this->options = $options;
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->createBaseQuery()
            ->addSelect('MAX(a.date) AS last');
    }

    public function getCount(): int
    {
        $query = $this->createBaseQuery()
            ->select('a.ip')
            ->getQuery();

        $parameters = [
            $query->getParameter('since')->getValue(),
            $query->getParameter('min')->getValue(),
            $query->getParameter('min')->getValue(),
        ];

        $stmt = $this->em->getConnection()->executeQuery(
            sprintf('SELECT COUNT(ipa.*) FROM (%s) ipa', $query->getSQL()),
            $parameters
        );

        return (int) $stmt->fetchColumn();
    }

    private function createBaseQuery()
    {
        $min = $this->options->get(Option::NETFLOW_MINIMUM_UNKNOWN_TRAFFIC, 1024);

        return $this->em->getRepository(IpAccounting::class)
            ->createQueryBuilder('a')
            ->resetDQLPart('select')
            ->groupBy('a.ip')
            ->having('SUM(a.upload) >= :min OR SUM(a.download) >= :min')
            ->andWhere('a.date >= :since')
            ->setParameter('since', (new \DateTime())->modify('-1 week')->format('Y-m-d'))
            ->setParameter('min', $min * 1024);
    }
}
