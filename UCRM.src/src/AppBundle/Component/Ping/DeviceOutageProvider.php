<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Ping;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeviceOutage;
use AppBundle\Entity\ServiceDeviceOutage;
use AppBundle\Entity\Site;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Nette\Utils\Html;

class DeviceOutageProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $deviceOutageRepository;

    /**
     * @var EntityRepository
     */
    private $serviceDeviceOutageRepository;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->deviceOutageRepository = $this->em->getRepository(DeviceOutage::class);
        $this->serviceDeviceOutageRepository = $this->em->getRepository(ServiceDeviceOutage::class);
    }

    public function getAllSitesForm(): array
    {
        $qb = $this->deviceOutageRepository->createQueryBuilder('do')
            ->select('s.id AS s_id, s.name AS s_name')
            ->join('do.device', 'd')
            ->join('d.site', 's')
            ->orderBy('s.name', 'ASC')
            ->andWhere('s.deletedAt IS NULL');
        $result = $qb->getQuery()->getArrayResult();
        $sites = [];

        foreach ($result as $item) {
            $sites[$item['s_id']] = $item['s_name'];
        }

        return $sites;
    }

    /**
     * @param Site $site
     */
    public function getDevicesForm(Site $site = null): array
    {
        $qb = $this->deviceOutageRepository->createQueryBuilder('do')
            ->select('d.id AS d_id, d.name AS d_name, s.name AS s_name')
            ->join('do.device', 'd')
            ->join('d.site', 's')
            ->orderBy('s.name', 'ASC')
            ->addOrderBy('d.name', 'ASC')
            ->andWhere('d.deletedAt IS NULL');

        if ($site) {
            $qb->andWhere('s.id = :site')
                ->setParameter('site', $site);
        }

        $result = $qb->getQuery()->getArrayResult();
        $devices = [];

        foreach ($result as $item) {
            if ($site) {
                $devices[$item['d_id']] = $item['d_name'];
            } else {
                $devices[$item['s_name']][$item['d_id']] = $item['d_name'];
            }
        }

        return $devices;
    }

    public function getDevicesFormHtml(Site $site = null): string
    {
        $devices = $this->getDevicesForm($site);

        $devices = ['' => '-'] + $devices;

        return $this->buildSelectOptions($devices);
    }

    public function getAllClientsForm(): array
    {
        $result = $this->em->getRepository(Client::class)->findBy(
            [
                'deletedAt' => null,
            ]
        );
        $clients = [];

        foreach ($result as $item) {
            $clients[$item->getId()] = $item->getNameForView();
        }

        uasort(
            $clients,
            function ($a, $b) {
                return strnatcmp($a, $b);
            }
        );

        return $clients;
    }

    public function getNetworkQueryBuilder(): QueryBuilder
    {
        return $this->deviceOutageRepository
            ->createQueryBuilder('do')
            ->addSelect('d.status AS d_status')
            ->join('do.device', 'd')
            ->join('d.site', 's')
            ->andWhere('d.deletedAt IS NULL');
    }

    public function getServiceQueryBuilder(): QueryBuilder
    {
        return $this->serviceDeviceOutageRepository
            ->createQueryBuilder('do')
            ->addSelect('d.status AS d_status, d, s, c, u')
            ->join('do.serviceDevice', 'd')
            ->join('d.service', 's')
            ->join('s.client', 'c')
            ->join('c.user', 'u')
            ->andWhere('s.deletedAt IS NULL');
    }

    public function getNetworkOngoingCount(): int
    {
        return $this->deviceOutageRepository
            ->createQueryBuilder('do')
            ->select('COUNT(do) AS outage_count')
            ->join('do.device', 'd')
            ->andWhere('do.outageEnd IS NULL')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('d.sendPingNotifications = TRUE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getServiceOngoingCount(): int
    {
        return $this->serviceDeviceOutageRepository
            ->createQueryBuilder('do')
            ->select('COUNT(do) AS outage_count')
            ->join('do.serviceDevice', 'd')
            ->join('d.service', 's')
            ->andWhere('do.outageEnd IS NULL')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('d.sendPingNotifications = TRUE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildSelectOptions(array $items): string
    {
        $html = [];

        foreach ($items as $value => $label) {
            if (is_array($label)) {
                $group = Html::el(
                    'optgroup',
                    [
                        'label' => $value,
                    ]
                );

                foreach ($label as $v => $l) {
                    $group->addHtml($this->createOption($v, $l));
                }

                $html[] = (string) $group;
            } else {
                $html[] = (string) $this->createOption($value, $label);
            }
        }

        return implode('', $html);
    }

    private function createOption(string $value, string $label): Html
    {
        $option = Html::el(
            'option',
            [
                'value' => $value,
            ]
        );
        $option->setText($label);

        return $option;
    }
}
