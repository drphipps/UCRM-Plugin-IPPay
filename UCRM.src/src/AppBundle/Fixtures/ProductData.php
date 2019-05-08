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
use AppBundle\Entity\Product;
use AppBundle\Entity\State;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Entity\Tax;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class ProductData extends BaseFixture implements OrderedFixtureInterface
{
    public const REFERENCE_DEFAULT_PRODUCT = 'product-default';

    public function load(ObjectManager $em)
    {
        assert($em instanceof EntityManager);
        $this->loadTestingProduct($em);

        $em->flush();
    }

    public function getOrder()
    {
        return 7;
    }

    private function loadTestingProduct(EntityManager $em): void
    {
        $product = new Product();

        $product->setName(self::REFERENCE_DEFAULT_PRODUCT);
        $product->setPrice(10);
        $product->setTax($this->getReference(TaxData::REFERENCE_DEFAULT_TAX));

        $em->persist($product);

        $this->addReference(self::REFERENCE_DEFAULT_PRODUCT, $product);
    }
}
