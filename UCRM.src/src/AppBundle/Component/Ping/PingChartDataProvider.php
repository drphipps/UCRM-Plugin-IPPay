<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Ping;

use AppBundle\Entity\Option;
use AppBundle\Entity\PingInterface;
use AppBundle\Entity\PingLongTerm;
use AppBundle\Entity\PingServiceLongTerm;
use AppBundle\Entity\PingServiceShortTerm;
use AppBundle\Entity\PingShortTerm;
use AppBundle\Service\Options;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

class PingChartDataProvider
{
    const TYPE_NETWORK = 0;
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

    /**
     * ChartDataProvider constructor.
     */
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

    /**
     * @throws \InvalidArgumentException
     */
    public function getDataForDevice(
        int $type,
        int $deviceId,
        int $resolution = self::RESOLUTION_SHORT_TERM
    ): array {
        switch ([$type, $resolution]) {
            case [self::TYPE_NETWORK, self::RESOLUTION_SHORT_TERM]:
                return $this->getShortTerm(PingShortTerm::class, $deviceId);
            case [self::TYPE_NETWORK, self::RESOLUTION_LONG_TERM]:
                return $this->getLongTerm(PingLongTerm::class, $deviceId);
            case [self::TYPE_SERVICE, self::RESOLUTION_SHORT_TERM]:
                return $this->getShortTerm(PingServiceShortTerm::class, $deviceId);
            case [self::TYPE_SERVICE, self::RESOLUTION_LONG_TERM]:
                return $this->getLongTerm(PingServiceLongTerm::class, $deviceId);
        }

        throw new \InvalidArgumentException();
    }

    /**
     * @return array
     */
    private function getShortTerm(string $entity, int $deviceId)
    {
        $now = new \DateTime((new \DateTime())->format('Y-m-d H:00:00'));
        // we need 48 hours of data - data is calculated for full hours only, thus the date must be 49 hours in the past
        $since = new \DateTime((new \DateTime('-49 hour'))->format('Y-m-d H:00:00'));
        $repository = $this->em->getRepository($entity);

        $qb = $repository->createQueryBuilder('p');
        $qb->where('p.device = :id')
            ->andWhere('p.time >= :since')
            ->andWhere('p.time < :now')
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
        $dataPing = [];
        $dataPacketLoss = [];
        while ($since < $now) {
            $timestamp = $since->format('Y-m-d H:00:00');
            $labels[] = $this->formatter->formatDate($since, Formatter::DEFAULT, Formatter::SHORT);
            $dataPing[$timestamp] = null;
            $dataPacketLoss[$timestamp] = null;

            $since->modify('+1 hour');
        }

        $this->fillData($result, $dataPing, $dataPacketLoss, 'Y-m-d H:00:00');

        return [
            'labels' => $labels,
            'ping' => [
                'label' => $this->translator->trans('Ping'),
                'data' => array_values($dataPing),
            ],
            'packetLoss' => [
                'label' => $this->translator->trans('Packet loss'),
                'data' => array_values($dataPacketLoss),
            ],
        ];
    }

    /**
     * @return array
     */
    private function getLongTerm(string $entity, int $deviceId)
    {
        $now = new \DateTime('midnight');
        // we need 60 days of data - data is calculated for full days only, thus the date must be 61 days in the past
        $since = new \DateTime('-61 days midnight');
        $repository = $this->em->getRepository($entity);

        $qb = $repository->createQueryBuilder('p');
        $qb->where('p.device = :id')
            ->andWhere('p.time >= :since')
            ->andWhere('p.time < :now')
            ->setParameters(
                [
                    'id' => $deviceId,
                    'since' => $since->format('Y-m-d'),
                    'now' => $now->format('Y-m-d'),
                ]
            );

        $result = $qb->getQuery()->getResult();
        if (! $result) {
            return [];
        }

        $labels = [];
        $dataPing = [];
        $dataPacketLoss = [];
        while ($since < $now) {
            $timestamp = $since->format('Y-m-d');
            $labels[] = $this->formatter->formatDate($since, Formatter::DEFAULT, Formatter::NONE);
            $dataPing[$timestamp] = null;
            $dataPacketLoss[$timestamp] = null;

            $since->modify('+1 day');
        }

        $this->fillData($result, $dataPing, $dataPacketLoss, 'Y-m-d');

        return [
            'labels' => $labels,
            'ping' => [
                'label' => $this->translator->trans('Ping'),
                'data' => array_values($dataPing),
            ],
            'packetLoss' => [
                'label' => $this->translator->trans('Packet loss'),
                'data' => array_values($dataPacketLoss),
            ],
        ];
    }

    /**
     * @param array|PingInterface[] $result
     */
    private function fillData(array $result, array &$dataPing, array &$dataPacketLoss, string $format)
    {
        foreach ($result as $point) {
            $time = clone $point->getTime();
            $time->setTimezone($this->timezone);
            $time = $time->format($format);

            if (! array_key_exists($time, $dataPing)) {
                continue;
            }
            $dataPing[$time] = round($point->getPing(), 2);
            $dataPacketLoss[$time] = round($point->getPacketLoss() * 100, 2);
        }
    }
}
