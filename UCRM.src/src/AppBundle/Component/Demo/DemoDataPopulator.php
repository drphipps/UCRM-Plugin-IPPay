<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo;

use AppBundle\Component\Demo\DataProvider\AppKeyDataProvider;
use AppBundle\Component\Demo\DataProvider\JobDataProvider;
use AppBundle\Component\Demo\DataProvider\PingDataProvider;
use AppBundle\Component\Demo\DataProvider\TicketDataProvider;
use AppBundle\Component\Demo\DataProvider\TrafficDataProvider;
use AppBundle\Component\Demo\DataProvider\WirelessStatisticsDataProvider;
use Doctrine\ORM\EntityManagerInterface;

class DemoDataPopulator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TrafficDataProvider
     */
    private $trafficDataProvider;

    /**
     * @var PingDataProvider
     */
    private $pingDataProvider;

    /**
     * @var WirelessStatisticsDataProvider
     */
    private $wirelessStatisticsDataProvider;

    /**
     * @var JobDataProvider
     */
    private $jobDataProvider;

    /**
     * @var TicketDataProvider
     */
    private $ticketDataProvider;

    /**
     * @var AppKeyDataProvider
     */
    private $appKeyDataProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        TrafficDataProvider $trafficDataProvider,
        PingDataProvider $pingDataProvider,
        WirelessStatisticsDataProvider $wirelessStatisticsDataProvider,
        JobDataProvider $jobDataProvider,
        TicketDataProvider $ticketDataProvider,
        AppKeyDataProvider $appKeyDataProvider
    ) {
        $this->entityManager = $entityManager;
        $this->trafficDataProvider = $trafficDataProvider;
        $this->pingDataProvider = $pingDataProvider;
        $this->wirelessStatisticsDataProvider = $wirelessStatisticsDataProvider;
        $this->jobDataProvider = $jobDataProvider;
        $this->ticketDataProvider = $ticketDataProvider;
        $this->appKeyDataProvider = $appKeyDataProvider;
    }

    public function populate(): void
    {
        $queries = [];
        $queries = array_merge($queries, $this->trafficDataProvider->get());
        $queries = array_merge($queries, $this->pingDataProvider->getShortTerm());
        $queries = array_merge($queries, $this->pingDataProvider->getLongTerm());
        $queries = array_merge($queries, $this->wirelessStatisticsDataProvider->getShortTerm());
        $queries = array_merge($queries, $this->wirelessStatisticsDataProvider->getLongTerm());
        $queries = array_merge($queries, $this->jobDataProvider->get());
        $queries = array_merge($queries, $this->ticketDataProvider->get());
        $queries = array_merge($queries, $this->appKeyDataProvider->get());

        foreach ($queries as $query) {
            if (is_array($query)) {
                $this->entityManager->getConnection()->executeQuery($query['query'], $query['params']);
            } else {
                $this->entityManager->getConnection()->executeQuery($query);
            }
        }
    }
}
