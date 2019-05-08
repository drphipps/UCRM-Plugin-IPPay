<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\NetFlow;

use AppBundle\Entity\IpAccounting;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;

class UnknownDevicesProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var string
     */
    private $timezone;

    public function __construct(EntityManager $em, Options $options)
    {
        $this->em = $em;
        $this->timezone = $options->get(Option::APP_TIMEZONE, 'UTC');
    }

    public function getLastWeekActivity(): array
    {
        $today = new \DateTimeImmutable('midnight', new \DateTimeZone($this->timezone));
        $since = $today->modify('-1 week');

        $qb = $this->em->getRepository(IpAccounting::class)->createQueryBuilder('a');
        $qb
            ->select('a.ip, SUM(a.upload) AS upload, SUM(a.download) AS download, MAX(a.date) AS last')
            ->andWhere('a.date >= :since')
            ->groupBy('a.ip')
            ->orderBy('a.ip')
            ->setParameter('since', $since->format('Y-m-d'));

        return $qb->getQuery()->getResult();
    }
}
