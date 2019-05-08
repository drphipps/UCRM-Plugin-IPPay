<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Fixtures;

use AppBundle\Entity\Country;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Financial\AccountStatementTemplate;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use AppBundle\Entity\Financial\QuoteTemplate;
use AppBundle\Entity\Organization;
use AppBundle\Entity\OrganizationBankAccount;
use AppBundle\Entity\PaymentReceiptTemplate;
use AppBundle\Entity\State;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class OrganizationData extends BaseFixture implements OrderedFixtureInterface
{
    public const REFERENCE_DEFAULT_ORGANIZATION = 'default-organization';
    public const REFERENCE_TARIFF_MINI = 'tariff-mini';
    public const REFERENCE_TARIFF_MIDDLE = 'tariff-middle';
    public const REFERENCE_TARIFF_MAXI = 'tariff-maxi';

    public function load(ObjectManager $em)
    {
        assert($em instanceof EntityManager);
        $this->loadDefaultOrganization($em);
        $this->loadTariffMini($em);
        $this->loadTariffMiddle($em);
        $this->loadTariffMaxi($em);

        $em->flush();
    }

    public function getOrder()
    {
        return 5;
    }

    private function loadDefaultOrganization(EntityManager $em): void
    {
        $bankAccount = new OrganizationBankAccount();
        $bankAccount->setName('Primary account');
        $bankAccount->setField1('123123123');
        $bankAccount->setField2('3400123456788');
        $em->persist($bankAccount);

        $organization = new Organization();
        $organization->setCountry($em->getReference(Country::class, 249));
        $organization->setState($em->getReference(State::class, 1));
        $organization->setBankAccount($bankAccount);
        $organization->setName('UBNT ISP');
        $organization->setCurrency($em->getReference(Currency::class, Currency::DEFAULT_ID));
        $organization->setEmail('ubnt@example.com');
        $organization->setStreet1('2580 Orchard Parkway');
        $organization->setCity('San Jose');
        $organization->setZipCode('95131');
        $organization->setInvoiceMaturityDays(14);
        $organization->setInvoiceNumberLength(6);
        $organization->setSelected(true);
        $organization->setInvoiceTemplate($em->getReference(InvoiceTemplate::class, 1));
        $organization->setProformaInvoiceTemplate($em->getReference(ProformaInvoiceTemplate::class, 1));
        $organization->setQuoteTemplate($em->getReference(QuoteTemplate::class, 1));
        $organization->setPaymentReceiptTemplate($em->getReference(PaymentReceiptTemplate::class, 1));
        $organization->setAccountStatementTemplate($em->getReference(AccountStatementTemplate::class, 1));
        $em->persist($organization);

        $this->addReference(self::REFERENCE_DEFAULT_ORGANIZATION, $organization);
    }

    private function loadTariffMini(EntityManager $em): void
    {
        $tariff = new Tariff();
        $tariff->setOrganization($this->getReference(self::REFERENCE_DEFAULT_ORGANIZATION));
        $tariff->setName('Mini');
        $tariff->setDownloadSpeed(10);
        $tariff->setUploadSpeed(10);
        $tariff->setTax($this->getReference(TaxData::REFERENCE_DEFAULT_TAX));
        $em->persist($tariff);

        $period = new TariffPeriod(1, true);
        $period->setTariff($tariff);
        $period->setPrice(10);
        $em->persist($period);

        $period = new TariffPeriod(3, true);
        $period->setTariff($tariff);
        $period->setPrice(25);
        $em->persist($period);

        $period = new TariffPeriod(6, true);
        $period->setTariff($tariff);
        $period->setPrice(50);
        $em->persist($period);

        $period = new TariffPeriod(12, false);
        $period->setTariff($tariff);
        $period->setPrice(90);
        $em->persist($period);

        $period = new TariffPeriod(24, false);
        $period->setTariff($tariff);
        $period->setPrice(170);
        $em->persist($period);

        $this->addReference(self::REFERENCE_TARIFF_MINI, $tariff);
    }

    private function loadTariffMiddle(EntityManager $em): void
    {
        $tariff = new Tariff();
        $tariff->setOrganization($this->getReference(self::REFERENCE_DEFAULT_ORGANIZATION));
        $tariff->setName('Middle');
        $tariff->setDownloadSpeed(50);
        $tariff->setUploadSpeed(50);
        $em->persist($tariff);

        $period = new TariffPeriod(1, true);
        $period->setTariff($tariff);
        $period->setPrice(20);
        $em->persist($period);

        $period = new TariffPeriod(3, true);
        $period->setTariff($tariff);
        $period->setPrice(50);
        $em->persist($period);

        $period = new TariffPeriod(6, true);
        $period->setTariff($tariff);
        $period->setPrice(100);
        $em->persist($period);

        $period = new TariffPeriod(12, false);
        $period->setTariff($tariff);
        $period->setPrice(180);
        $em->persist($period);

        $period = new TariffPeriod(24, false);
        $period->setTariff($tariff);
        $period->setPrice(320);
        $em->persist($period);

        $this->addReference(self::REFERENCE_TARIFF_MIDDLE, $tariff);
    }

    private function loadTariffMaxi(EntityManager $em): void
    {
        $tariff = new Tariff();
        $tariff->setOrganization($this->getReference(self::REFERENCE_DEFAULT_ORGANIZATION));
        $tariff->setName('Maxi');
        $tariff->setDownloadSpeed(200);
        $tariff->setUploadSpeed(200);
        $em->persist($tariff);

        $period = new TariffPeriod(1, true);
        $period->setTariff($tariff);
        $period->setPrice(50);
        $em->persist($period);

        $period = new TariffPeriod(3, true);
        $period->setTariff($tariff);
        $period->setPrice(130);
        $em->persist($period);

        $period = new TariffPeriod(6, true);
        $period->setTariff($tariff);
        $period->setPrice(250);
        $em->persist($period);

        $period = new TariffPeriod(12, false);
        $period->setTariff($tariff);
        $period->setPrice(450);
        $em->persist($period);

        $period = new TariffPeriod(24, false);
        $period->setTariff($tariff);
        $period->setPrice(800);
        $em->persist($period);

        $this->addReference(self::REFERENCE_TARIFF_MAXI, $tariff);
    }
}
