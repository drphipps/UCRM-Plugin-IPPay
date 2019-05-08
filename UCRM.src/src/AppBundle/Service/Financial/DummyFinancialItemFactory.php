<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

use AppBundle\Entity\Financial\FinancialItemFeeInterface;
use AppBundle\Entity\Financial\FinancialItemInterface;
use AppBundle\Entity\Financial\FinancialItemOtherInterface;
use AppBundle\Entity\Financial\FinancialItemProductInterface;
use AppBundle\Entity\Financial\FinancialItemServiceInterface;
use AppBundle\Entity\Financial\FinancialItemSurchargeInterface;
use AppBundle\Entity\Financial\InvoiceItemFee;
use AppBundle\Entity\Financial\InvoiceItemOther;
use AppBundle\Entity\Financial\InvoiceItemProduct;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Financial\InvoiceItemSurcharge;
use AppBundle\Entity\Financial\QuoteItemFee;
use AppBundle\Entity\Financial\QuoteItemOther;
use AppBundle\Entity\Financial\QuoteItemProduct;
use AppBundle\Entity\Financial\QuoteItemService;
use AppBundle\Entity\Financial\QuoteItemSurcharge;
use AppBundle\Entity\Service;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Entity\Tax;
use AppBundle\Util\Invoicing;
use Faker\Factory;
use Faker\Generator;

/**
 * Used only in DummyFinancialFactory to create dummy (random) data items.
 * The dummy invoice / quote is then used for template preview and validation.
 */
class DummyFinancialItemFactory
{
    /**
     * @var Generator
     */
    private $faker;

    /**
     * @var Tax
     */
    private $tax;

    public function __construct()
    {
        $this->faker = Factory::create();
        $this->faker->seed();

        $this->tax = new Tax();
        $this->tax->setRate($this->faker->numberBetween(1, 99));
        $this->tax->setName('Tax');
    }

    public function createInvoiceItemService(int $discountType): InvoiceItemService
    {
        $item = new InvoiceItemService();
        $this->setItemServiceData($item, $discountType);

        return $item;
    }

    public function createQuoteItemService(int $discountType): QuoteItemService
    {
        $item = new QuoteItemService();
        $this->setItemServiceData($item, $discountType);

        return $item;
    }

    public function createInvoiceItemSurcharge(Service $service): InvoiceItemSurcharge
    {
        $item = new InvoiceItemSurcharge();
        $this->setItemSurchargeData($item, $service);

        return $item;
    }

    public function createQuoteItemSurcharge(Service $service): QuoteItemSurcharge
    {
        $item = new QuoteItemSurcharge();
        $this->setItemSurchargeData($item, $service);

        return $item;
    }

    public function createInvoiceItemProduct(): InvoiceItemProduct
    {
        $item = new InvoiceItemProduct();
        $this->setItemProductData($item);

        return $item;
    }

    public function createQuoteItemProduct(): QuoteItemProduct
    {
        $item = new QuoteItemProduct();
        $this->setItemProductData($item);

        return $item;
    }

    public function createInvoiceItemOther(): InvoiceItemOther
    {
        $item = new InvoiceItemOther();
        $this->setItemOtherData($item);

        return $item;
    }

    public function createQuoteItemOther(): QuoteItemOther
    {
        $item = new QuoteItemOther();
        $this->setItemOtherData($item);

        return $item;
    }

    public function createInvoiceItemFee(): InvoiceItemFee
    {
        $item = new InvoiceItemFee();
        $this->setItemFeeData($item);

        return $item;
    }

    public function createQuoteItemFee(): QuoteItemFee
    {
        $item = new QuoteItemFee();
        $this->setItemFeeData($item);

        return $item;
    }

    private function setItemServiceData(FinancialItemServiceInterface $item, int $discountType): void
    {
        $service = new Service();
        $service->setId($this->faker->numberBetween());
        $service->setInvoicingPeriodType(
            $this->faker->randomElement(
                [
                    Service::INVOICING_BACKWARDS,
                    Service::INVOICING_FORWARDS,
                ]
            )
        );

        $item->setService($service);
        $item->setLabel(sprintf('Service: %s', $this->faker->sentence(2)));
        $item->setPrice($this->faker->randomFloat(2, 10, 1000));
        $item->setInvoicedFrom($this->faker->dateTimeBetween('-1 year', '-1 month'));
        $item->setInvoicedTo($this->faker->dateTimeBetween('-1 month'));
        $item->setQuantity(
            Invoicing::getPeriodQuantity(
                $item->getInvoicedFrom(),
                $item->getInvoicedTo(),
                $this->faker->randomElement(TariffPeriod::PERIODS),
                $this->faker->randomElement(array_keys(Service::INVOICING_PERIOD_START_DAY))
            )
        );
        $item->setTotal($this->calculateTotal($item));
        $item->setTax1(random_int(0, 1) ? $this->tax : null);

        if ($discountType !== Service::DISCOUNT_NONE) {
            $item->setDiscountType($discountType);
            $item->setDiscountInvoiceLabel(sprintf('Discount: %s', $this->faker->sentence(2)));
            $item->setDiscountPrice($this->faker->randomFloat(2, 10, 1000) * -1);
            $item->setDiscountQuantity($item->getQuantity());
            $item->setDiscountTotal($item->getDiscountPrice() * $item->getDiscountQuantity());
            $item->setDiscountFrom($this->faker->dateTimeBetween('-1 year', '-1 month'));
            $item->setDiscountTo($this->faker->dateTimeBetween('-1 month'));
        }
    }

    private function setItemSurchargeData(FinancialItemSurchargeInterface $item, Service $service): void
    {
        $item->setService($service);

        $item->setTaxable(true);
        $item->setTax1(random_int(0, 1) ? $this->tax : null);
        $item->setLabel(sprintf('Surcharge: %s', $this->faker->sentence(2)));
        $item->setPrice($this->faker->randomFloat(2, 10, 1000));
        $item->setQuantity($this->faker->randomFloat(2, 0.1, 10));
        $item->setTotal($this->calculateTotal($item));
    }

    private function setItemProductData(FinancialItemProductInterface $item): void
    {
        $item->setLabel(sprintf('Product: %s', $this->faker->sentence(2)));
        $item->setPrice($this->faker->randomFloat(2, 10, 1000));
        $item->setQuantity($this->faker->randomFloat(2, 0.1, 10));
        $item->setTotal($this->calculateTotal($item));
        $item->setTax1(random_int(0, 1) ? $this->tax : null);
        $item->setUnit(array_rand(array_flip(['km', 'mm', 'm', 'mm', 'kg', 'g', 'mg'])));
    }

    private function setItemOtherData(FinancialItemOtherInterface $item): void
    {
        $item->setLabel(sprintf('Custom: %s', $this->faker->sentence(2)));
        $item->setPrice($this->faker->randomFloat(2, 10, 1000));
        $item->setQuantity($this->faker->randomFloat(2, 0.1, 10));
        $item->setTotal($this->calculateTotal($item));
        $item->setTaxable(true);
        $item->setTax1(random_int(0, 1) ? $this->tax : null);
        $item->setUnit(array_rand(array_flip(['km', 'mm', 'm', 'mm', 'kg', 'g', 'mg'])));
    }

    private function setItemFeeData(FinancialItemFeeInterface $item): void
    {
        $item->setLabel(sprintf('Fee: %s', $this->faker->sentence(2)));
        $item->setPrice($this->faker->randomFloat(2, 10, 1000));
        $item->setQuantity($this->faker->randomFloat(2, 0.1, 10));
        $item->setTotal($this->calculateTotal($item));
        $item->setTaxable(true);
        $item->setTax1(random_int(0, 1) ? $this->tax : null);
    }

    private function calculateTotal(FinancialItemInterface $item): float
    {
        $total = $item->getPrice() * $item->getQuantity();
        assert(is_float($total));

        return $total;
    }
}
