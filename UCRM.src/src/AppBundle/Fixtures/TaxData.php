<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Fixtures;

use AppBundle\Entity\Country;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Entity\Organization;
use AppBundle\Entity\OrganizationBankAccount;
use AppBundle\Entity\State;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Entity\Tax;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class TaxData extends BaseFixture implements OrderedFixtureInterface
{
    public const REFERENCE_DEFAULT_TAX = 'tax-default';
    public const REFERENCE_NON_DEFAULT_TAX = 'tax-non-default';

    public function load(ObjectManager $em)
    {
        assert($em instanceof EntityManager);
        $this->loadDefaultTax($em);
        $this->loadNonDefaultTax($em);

        $em->flush();
    }

    public function getOrder()
    {
        return 3;
    }

    private function loadDefaultTax(EntityManager $em): void
    {
        $tax = new Tax();

        $tax->setName(self::REFERENCE_DEFAULT_TAX);
        $tax->setRate(0);
        $tax->setSelected(true);

        $em->persist($tax);

        $this->addReference(self::REFERENCE_DEFAULT_TAX, $tax);
    }

    private function loadNonDefaultTax(EntityManager $em): void
    {
        $tax = new Tax();

        $tax->setName(self::REFERENCE_NON_DEFAULT_TAX);
        $tax->setRate(0);

        $em->persist($tax);

        $this->addReference(self::REFERENCE_NON_DEFAULT_TAX, $tax);
    }
}
