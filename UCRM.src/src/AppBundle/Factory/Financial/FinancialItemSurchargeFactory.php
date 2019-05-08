<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial;

use AppBundle\Entity\Financial\FinancialItemSurchargeInterface;
use AppBundle\Entity\Financial\InvoiceItemSurcharge;
use AppBundle\Entity\Financial\QuoteItemSurcharge;
use AppBundle\Entity\ServiceSurcharge;

class FinancialItemSurchargeFactory
{
    public function createInvoiceItem(ServiceSurcharge $serviceSurcharge): InvoiceItemSurcharge
    {
        $item = new InvoiceItemSurcharge();
        $this->setData($item, $serviceSurcharge);

        return $item;
    }

    public function createQuoteItem(ServiceSurcharge $serviceSurcharge): QuoteItemSurcharge
    {
        $item = new QuoteItemSurcharge();
        $this->setData($item, $serviceSurcharge);

        return $item;
    }

    private function setData(
        FinancialItemSurchargeInterface $item,
        ServiceSurcharge $serviceSurcharge
    ): void {
        $service = $serviceSurcharge->getService();

        $item->setService($service);
        $item->setServiceSurcharge($serviceSurcharge);
        $item->setLabel($serviceSurcharge->getInvoiceLabelForView());

        $item->setTaxable($serviceSurcharge->getTaxable());
        if ($serviceSurcharge->getTaxable()) {
            if ($serviceSurcharge->getSurcharge()->getTax()) {
                $item->setTax1($serviceSurcharge->getSurcharge()->getTax());
            } elseif (
                $service->getTariff()
                && $service->getTariff()->getTaxable()
                && $service->getTariff()->getTax()
            ) {
                $item->setTax1($service->getTariff()->getTax());
            } else {
                $item->setTax1($service->getTax1());
                $item->setTax2($service->getTax2());
                $item->setTax3($service->getTax3());
            }
        }

        $item->setPrice($serviceSurcharge->getInheritedPrice());
        $item->setQuantity(1.0);
        $item->setTotal($item->getPrice() * $item->getQuantity());
    }
}
