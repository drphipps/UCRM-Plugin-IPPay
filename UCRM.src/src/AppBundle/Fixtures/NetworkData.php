<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Fixtures;

use AppBundle\Entity\Client;
use AppBundle\Entity\Country;
use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceSurcharge;
use AppBundle\Entity\Site;
use AppBundle\Entity\State;
use AppBundle\Entity\Vendor;
use AppBundle\Service\Invoice\InvoiceBuilderFactory;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class NetworkData extends BaseFixture implements OrderedFixtureInterface
{
    public const REFERENCE_SITE = 'site';
    public const REFERENCE_DEVICE_EDGE_OS = 'device-edge-os';

    public function load(ObjectManager $em)
    {
        assert($em instanceof EntityManager);
        $this->loadSite($em);
        $this->loadDeviceEdgeOs($em);

        $em->flush();
    }

    public function getOrder()
    {
        return 10;
    }

    private function loadSite(EntityManager $em)
    {
        $site = new Site();
        $site->setName('Testing site');
        $site->setAddress($this->faker->format('address'));
        $em->persist($site);
        $this->addReference(self::REFERENCE_SITE, $site);
    }

    private function loadDeviceEdgeOs(EntityManager $em)
    {
        $device = new Device();
        $device->setName('Edge OS device');
        $device->setVendor($em->getReference(Vendor::class, Vendor::EDGE_OS));
        $device->setSite($this->getReference(self::REFERENCE_SITE));
        $em->persist($device);
        $this->addReference(self::REFERENCE_DEVICE_EDGE_OS, $device);

        $interface = new DeviceInterface();
        $interface->setDevice($device);
        $interface->setName('Ethernet interface');
        $interface->setType(DeviceInterface::TYPE_ETHERNET);
        $device->addInterface($interface);
        $em->persist($interface);
    }
}
