<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\NetFlow;

use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceAccounting;
use AppBundle\Entity\ServiceAccountingCorrection;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;

class NetFlowChartDataProvider
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

    public function getChartDataForDashboard(): array
    {
        $data = $this->getChartData(null, '-7 days');
        $downloadTotal = 0;
        $uploadTotal = 0;

        foreach ($data as $item) {
            $downloadTotal += $item['download'];
            $uploadTotal += $item['upload'];
        }

        return [
            'downloadTotal' => $downloadTotal,
            'uploadTotal' => $uploadTotal,
            'chart' => $data,
        ];
    }

    public function getChartDataForService(Service $service): array
    {
        return $this->getChartData($service);
    }

    public function hasData(Service $service, string $period = '-90 days'): bool
    {
        $today = new \DateTimeImmutable('midnight', new \DateTimeZone($this->timezone));
        $since = $today->modify($period);

        return $this->em->getRepository(ServiceAccounting::class)->hasData($since, $service)
            || $this->em->getRepository(ServiceAccountingCorrection::class)->hasData($since, $service);
    }

    private function getChartData(Service $service = null, string $period = '-90 days'): array
    {
        $today = new \DateTimeImmutable('midnight', new \DateTimeZone($this->timezone));
        $since = $today->modify($period);

        $netflowData = $this->em->getRepository(ServiceAccounting::class)->getChartData($since, $service);
        $correctionData = $this->em->getRepository(ServiceAccountingCorrection::class)->getChartData($since, $service);

        $data = [];
        for ($day = $since; $day <= $today; $day = $day->modify('+1 day')) {
            $correctionDownload = 0;
            $correctionUpload = 0;
            foreach ($correctionData as $correction) {
                if ($day->format('Y-m-d') === \DateTimeImmutable::createFromMutable($correction['date'])->format('Y-m-d')) {
                    $correctionDownload = $correction['download'];
                    $correctionUpload = $correction['upload'];
                }
            }

            $element = reset($netflowData);
            if ($element && $element['date']->format('Y-m-d') === $day->format('Y-m-d')) {
                $data[] = [
                    'date' => $element['date'],
                    'upload' => (int) $element['upload'] + $correctionUpload,
                    'download' => (int) $element['download'] + $correctionDownload,
                ];
                array_shift($netflowData);
            } else {
                $data[] = [
                    'date' => $day,
                    'upload' => 0 + $correctionUpload,
                    'download' => 0 + $correctionDownload,
                ];
            }
        }

        return array_values($data);
    }
}
