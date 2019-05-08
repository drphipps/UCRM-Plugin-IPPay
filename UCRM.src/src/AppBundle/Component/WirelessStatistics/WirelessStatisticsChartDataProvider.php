<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\WirelessStatistics;

use AppBundle\Entity\Option;
use AppBundle\Entity\WirelessStatisticsInterface;
use AppBundle\Entity\WirelessStatisticsLongTerm;
use AppBundle\Entity\WirelessStatisticsServiceLongTerm;
use AppBundle\Entity\WirelessStatisticsServiceShortTerm;
use AppBundle\Entity\WirelessStatisticsShortTerm;
use AppBundle\Service\Options;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

class WirelessStatisticsChartDataProvider
{
    const TYPE_DEVICE = 0;
    const TYPE_SERVICE = 1;

    const RESOLUTION_SHORT_TERM = 0;
    const RESOLUTION_LONG_TERM = 1;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    public function __construct(
        EntityManager $em,
        Formatter $formatter,
        TranslatorInterface $translator,
        Options $options
    ) {
        $this->em = $em;
        $this->formatter = $formatter;
        $this->translator = $translator;
        $this->timezone = new \DateTimeZone($options->get(Option::APP_TIMEZONE, 'UTC'));
    }

    public function getDataForDevice(
        int $type,
        int $deviceId,
        int $resolution = self::RESOLUTION_SHORT_TERM
    ): array {
        switch ([$type, $resolution]) {
            case [self::TYPE_DEVICE, self::RESOLUTION_SHORT_TERM]:
                return $this->getShortTerm(WirelessStatisticsShortTerm::class, $deviceId);
            case [self::TYPE_DEVICE, self::RESOLUTION_LONG_TERM]:
                return $this->getLongTerm(WirelessStatisticsLongTerm::class, $deviceId);
            case [self::TYPE_SERVICE, self::RESOLUTION_SHORT_TERM]:
                return $this->getShortTerm(WirelessStatisticsServiceShortTerm::class, $deviceId);
            case [self::TYPE_SERVICE, self::RESOLUTION_LONG_TERM]:
                return $this->getLongTerm(WirelessStatisticsServiceLongTerm::class, $deviceId);
            default:
                return [];
        }
    }

    /**
     * @return array
     */
    private function getShortTerm(string $entity, int $deviceId)
    {
        $now = new \DateTime((new \DateTime())->format('Y-m-d H:00:00'));
        $since = new \DateTime((new \DateTime('-49 hour'))->format('Y-m-d H:00:00'));
        $repository = $this->em->getRepository($entity);

        if ($entity === WirelessStatisticsServiceShortTerm::class) {
            $where = 'p.serviceDevice = :id';
        } else {
            $where = 'p.device = :id';
        }

        $qb = $repository->createQueryBuilder('p');
        $qb->where($where)
            ->andWhere('p.time >= :since')
            ->andWhere('p.time <= :now')
            ->setParameters(
                [
                    'id' => $deviceId,
                    'since' => $since->format('Y-m-d H:00:00O'),
                    'now' => $now->format('Y-m-d H:00:00O'),
                ]
            );

        $result = $qb->getQuery()->getResult();
        if (! $result) {
            return [];
        }

        $labels = [];
        $dataCcq = [];
        $dataRxRate = [];
        $dataTxRate = [];
        $dataSignal = [];
        $dataRemoteSignal = [];

        while ($since < $now) {
            $timestamp = $since->format('Y-m-d H:00:00');
            $labels[] = $this->formatter->formatDate($since, Formatter::DEFAULT, Formatter::SHORT);

            $dataCcq[$timestamp] = null;
            $dataRxRate[$timestamp] = null;
            $dataTxRate[$timestamp] = null;
            $dataSignal[$timestamp] = null;
            $dataRemoteSignal[$timestamp] = null;

            $since->modify('+1 hour');
        }

        $this->fillData($result, $dataCcq, $dataSignal, $dataRxRate, $dataTxRate, $dataRemoteSignal, 'Y-m-d H:00:00');

        return $this->generateResponse(
            $labels,
            array_values($dataCcq),
            array_values($dataSignal),
            array_values($dataRxRate),
            array_values($dataTxRate),
            array_values($dataRemoteSignal)
        );
    }

    /**
     * @return array
     */
    private function getLongTerm(string $entity, int $deviceId)
    {
        $now = new \DateTime('midnight');
        $since = new \DateTime('-61 days midnight');
        $repository = $this->em->getRepository($entity);

        if ($entity === WirelessStatisticsServiceLongTerm::class) {
            $where = 'p.serviceDevice = :id';
        } else {
            $where = 'p.device = :id';
        }

        $qb = $repository->createQueryBuilder('p');
        $qb->where($where)
            ->andWhere('p.time >= :since')
            ->andWhere('p.time <= :now')
            ->setParameters(
                [
                    'id' => $deviceId,
                    'since' => $since,
                    'now' => $now,
                ]
            );

        $result = $qb->getQuery()->getResult();
        if (! $result) {
            return [];
        }

        $labels = [];
        $dataCcq = [];
        $dataRxRate = [];
        $dataTxRate = [];
        $dataSignal = [];
        $dataRemoteSignal = [];

        while ($since < $now) {
            $timestamp = $since->format('Y-m-d');
            $labels[] = $this->formatter->formatDate($since, Formatter::DEFAULT, Formatter::NONE);

            $dataCcq[$timestamp] = null;
            $dataRxRate[$timestamp] = null;
            $dataTxRate[$timestamp] = null;
            $dataSignal[$timestamp] = null;
            $dataRemoteSignal[$timestamp] = null;

            $since->modify('+1 day');
        }

        $this->fillData($result, $dataCcq, $dataSignal, $dataRxRate, $dataTxRate, $dataRemoteSignal, 'Y-m-d');

        return $this->generateResponse(
            $labels,
            array_values($dataCcq),
            array_values($dataSignal),
            array_values($dataRxRate),
            array_values($dataTxRate),
            array_values($dataRemoteSignal)
        );
    }

    private function generateResponse(
        array $labels = [],
        array $ccq = [],
        array $signal = [],
        array $rxRate = [],
        array $txRate = [],
        array $remoteSignal = []
    ): array {
        return [
            'labels' => $labels,
            'ccq' => [
                'label' => $this->translator->trans('CCQ'),
                'data' => $ccq,
            ],
            'signal' => [
                'label' => $this->translator->trans('Signal'),
                'data' => $signal,
            ],
            'rxRate' => [
                'label' => $this->translator->trans('RX rate'),
                'data' => $rxRate,
            ],
            'txRate' => [
                'label' => $this->translator->trans('TX rate'),
                'data' => $txRate,
            ],
            'remoteSignal' => [
                'label' => $this->translator->trans('Remote signal'),
                'data' => $remoteSignal,
            ],
        ];
    }

    /**
     * @param array|WirelessStatisticsInterface[] $result
     */
    private function fillData(
        array $result,
        array &$dataCcq,
        array &$dataSignal,
        array &$dataRxRate,
        array &$dataTxRate,
        array &$dataRemoteSignal,
        string $format
    ) {
        foreach ($result as $point) {
            $time = clone $point->getTime();
            $time->setTimezone($this->timezone);
            $time = $time->format($format);

            if (! array_key_exists($time, $dataCcq)) {
                continue;
            }

            $dataCcq[$time] = $point->getCcq();
            $dataRxRate[$time] = $point->getRxRate();
            $dataTxRate[$time] = $point->getTxRate();
            $dataSignal[$time] = $point->getSignal() * -1;
            $dataRemoteSignal[$time] = $point->getRemoteSignal() * -1;
        }
    }
}
