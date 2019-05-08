<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Fixtures;

use AppBundle\Entity\Surcharge;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class SurchargeData extends BaseFixture implements OrderedFixtureInterface
{
    public const REFERENCE_SURCHARGE_WIFI = 'surcharge-wifi';

    public function load(ObjectManager $em)
    {
        assert($em instanceof EntityManager);
        $this->loadSurchargeWifi($em);

        $em->flush();
    }

    public function getOrder()
    {
        return 7;
    }

    private function loadSurchargeWifi(EntityManager $em): void
    {
        $surcharge = new Surcharge();
        $surcharge->setName(self::REFERENCE_SURCHARGE_WIFI);
        $surcharge->setPrice(100);
        $surcharge->setTax($this->getReference(TaxData::REFERENCE_DEFAULT_TAX));
        $em->persist($surcharge);

        $this->addReference(self::REFERENCE_SURCHARGE_WIFI, $surcharge);
    }
}
