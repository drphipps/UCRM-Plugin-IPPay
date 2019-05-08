<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\NetFlow;

use AppBundle\DataProvider\AbstractTrafficDataProvider;
use AppBundle\DataProvider\ClientTopTrafficDataProvider;
use AppBundle\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;

class TopTrafficChartDataProvider extends AbstractTrafficDataProvider
{
    public const TYPE_DOWNLOAD = 'download';
    public const TYPE_UPLOAD = 'upload';

    private const TYPES = [
        self::TYPE_DOWNLOAD,
        self::TYPE_UPLOAD,
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ClientTopTrafficDataProvider
     */
    private $clientTopTrafficDataProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        ClientTopTrafficDataProvider $clientTopTrafficDataProvider
    ) {
        $this->entityManager = $entityManager;
        $this->clientTopTrafficDataProvider = $clientTopTrafficDataProvider;
    }

    public function getChartData(string $type, string $period): array
    {
        [$fromCurrent, $toCurrent, $fromPrevious, $toPrevious] = $this->getPeriod($period);

        switch ($type) {
            case self::TYPE_DOWNLOAD:
                return [
                    'data' => $this->getDownloadChartData($fromCurrent, $toCurrent, $fromPrevious, $toPrevious),
                    'fromCurrent' => $fromCurrent,
                    'toCurrent' => $toCurrent,
                ];

                break;
            case self::TYPE_UPLOAD:
                return [
                    'data' => $this->getUploadChartData($fromCurrent, $toCurrent, $fromPrevious, $toPrevious),
                    'fromCurrent' => $fromCurrent,
                    'toCurrent' => $toCurrent,
                ];

                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'Unknown type "%s", can be: %s',
                        $type,
                        implode(', ', self::TYPES)
                    )
                );
        }
    }

    private function getDownloadChartData(
        \DateTimeImmutable $fromCurrent,
        \DateTimeImmutable $toCurrent,
        \DateTimeImmutable $fromPrevious,
        \DateTimeImmutable $toPrevious
    ): array {
        $downloadersCurrent = $this->clientTopTrafficDataProvider->getTopTraffic(
            ClientTopTrafficDataProvider::TYPE_DOWNLOAD,
            $fromCurrent,
            $toCurrent,
            5
        );
        $downloadChart = $this->getCurrentChartData($downloadersCurrent);

        $downloadersPrevious = $downloadChart
            ? $this->clientTopTrafficDataProvider->getTopTraffic(
                ClientTopTrafficDataProvider::TYPE_DOWNLOAD,
                $fromPrevious,
                $toPrevious,
                5,
                array_keys($downloadChart)
            )
            : [];

        $this->includePreviousChartData($downloadersPrevious, $downloadChart);
        $maxTraffic = $this->getMaxTraffic($downloadChart);
        $this->calculateBarLengths($maxTraffic, $downloadChart);

        $average = $this->clientTopTrafficDataProvider->getAverage(
            ClientTopTrafficDataProvider::TYPE_DOWNLOAD,
            $fromCurrent,
            $toCurrent
        );

        return [
            'chart' => $downloadChart,
            'average' => $average,
            'lengthAverage' => $average && $maxTraffic
                ? round($average / $maxTraffic, 4) * 100
                : null,
        ];
    }

    private function getUploadChartData(
        \DateTimeImmutable $fromCurrent,
        \DateTimeImmutable $toCurrent,
        \DateTimeImmutable $fromPrevious,
        \DateTimeImmutable $toPrevious
    ): array {
        $uploadersCurrent = $this->clientTopTrafficDataProvider->getTopTraffic(
            ClientTopTrafficDataProvider::TYPE_UPLOAD,
            $fromCurrent,
            $toCurrent,
            5
        );
        $uploadChart = $this->getCurrentChartData($uploadersCurrent);

        $uploadersPrevious = $uploadChart
            ? $this->clientTopTrafficDataProvider->getTopTraffic(
                ClientTopTrafficDataProvider::TYPE_UPLOAD,
                $fromPrevious,
                $toPrevious,
                5,
                array_keys($uploadChart)
            )
            : [];

        $this->includePreviousChartData($uploadersPrevious, $uploadChart);
        $maxTraffic = $this->getMaxTraffic($uploadChart);
        $this->calculateBarLengths($maxTraffic, $uploadChart);

        $average = $this->clientTopTrafficDataProvider->getAverage(
            ClientTopTrafficDataProvider::TYPE_UPLOAD,
            $fromCurrent,
            $toCurrent
        );

        return [
            'chart' => $uploadChart,
            'average' => $average,
            'lengthAverage' => $average && $maxTraffic
                ? round($average / $maxTraffic, 4) * 100
                : null,
        ];
    }

    private function getCurrentChartData(array $trafficData): array
    {
        $chart = [];
        foreach ($trafficData as $item) {
            /** @var Client $client */
            $client = $item['client'];

            $chart[$client->getId()] = [
                'client' => $client,
                'trafficCurrent' => (float) $item['traffic'],
                'trafficPrevious' => null,
                'trafficDifference' => null,
                'lengthCurrent' => 0,
                'lengthPrevious' => 0,
            ];
        }

        return $chart;
    }

    private function includePreviousChartData(array $trafficData, array &$chartData): void
    {
        foreach ($trafficData as $item) {
            /** @var Client $client */
            $client = $item['client'];

            $trafficPrevious = (float) $item['traffic'];
            $chartData[$client->getId()]['trafficPrevious'] = $trafficPrevious;

            $trafficCurrent = $chartData[$client->getId()]['trafficCurrent'];
            $chartData[$client->getId()]['trafficDifference'] =
                round($trafficCurrent, 2) === 0.0 || round($trafficPrevious, 2) === 0.0
                    ? null
                    : round((1 - $trafficCurrent / $trafficPrevious) * 100) * -1;
        }
    }

    private function getMaxTraffic(array $chartData): float
    {
        $max = 0.0;
        foreach ($chartData as $item) {
            $max = max($max, $item['trafficCurrent'], $item['trafficPrevious']);
        }

        // include some gap, so that the chart bars are not full width
        $max *= 1.3;

        return $max;
    }

    private function calculateBarLengths(float $maxTraffic, array &$chartData): void
    {
        foreach ($chartData as $key => $item) {
            $chartData[$key]['lengthCurrent'] = $item['trafficCurrent'] && $maxTraffic
                ? round($item['trafficCurrent'] / $maxTraffic, 4) * 100
                : null;
            $chartData[$key]['lengthPrevious'] = $item['trafficPrevious'] && $maxTraffic
                ? round($item['trafficPrevious'] / $maxTraffic, 4) * 100
                : null;
        }
    }
}
