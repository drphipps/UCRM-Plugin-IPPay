<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Transformer\Financial;

use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\FinancialItemFeeInterface;
use AppBundle\Entity\Financial\FinancialItemInterface;
use AppBundle\Entity\Financial\FinancialItemOtherInterface;
use AppBundle\Entity\Financial\FinancialItemProductInterface;
use AppBundle\Entity\Financial\FinancialItemServiceInterface;
use AppBundle\Entity\Financial\FinancialItemSurchargeInterface;
use AppBundle\Entity\Financial\InvoiceItem;
use AppBundle\Entity\Financial\InvoiceItemFee;
use AppBundle\Entity\Financial\InvoiceItemOther;
use AppBundle\Entity\Financial\InvoiceItemProduct;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Financial\InvoiceItemSurcharge;

class FinancialToInvoiceTransformer
{
    /**
     * @return InvoiceItem[]
     */
    public function getInvoiceItemsFromFinancial(FinancialInterface $financial): array
    {
        foreach ($financial->getItems() as $item) {
            switch (true) {
                case $item instanceof FinancialItemFeeInterface:
                    $financialItems[] = $this->convertItemFee($item);
                    break;
                case $item instanceof FinancialItemOtherInterface:
                    $financialItems[] = $this->convertItemOther($item);
                    break;
                case $item instanceof FinancialItemProductInterface:
                    $financialItems[] = $this->convertItemProduct($item);
                    break;
                case $item instanceof FinancialItemServiceInterface:
                    $financialItems[] = $this->convertItemService($item);
                    break;
                case $item instanceof FinancialItemSurchargeInterface:
                    $financialItems[] = $this->convertItemSurcharge($item);
                    break;
            }
        }

        return $financialItems ?? [];
    }

    private function convertItemFee(FinancialItemFeeInterface $from): InvoiceItemFee
    {
        $to = new InvoiceItemFee();
        $this->setBaseData($from, $to);
        $to->setFee($from->getFee());

        return $to;
    }

    private function convertItemOther(FinancialItemOtherInterface $from): InvoiceItemOther
    {
        $to = new InvoiceItemOther();
        $this->setBaseData($from, $to);
        $to->setUnit($from->getUnit());

        return $to;
    }

    private function convertItemProduct(FinancialItemProductInterface $from): InvoiceItemProduct
    {
        $to = new InvoiceItemProduct();
        $this->setBaseData($from, $to);
        $to->setUnit($from->getUnit());
        $to->setProduct($from->getProduct());

        return $to;
    }

    private function convertItemService(FinancialItemServiceInterface $from): InvoiceItemService
    {
        $to = new InvoiceItemService();
        $this->setBaseData($from, $to);
        $to->setDiscountType($from->getDiscountType());
        $to->setDiscountValue($from->getDiscountValue());
        $to->setDiscountInvoiceLabel($from->getDiscountInvoiceLabel());
        $to->setDiscountFrom($this->cloneDateTime($from->getDiscountFrom()));
        $to->setDiscountTo($this->cloneDateTime($from->getDiscountTo()));
        $to->setDiscountQuantity($from->getDiscountQuantity());
        $to->setDiscountPrice($from->getDiscountPrice());
        $to->setDiscountTotal($from->getDiscountTotal());
        $to->setInvoicedFrom($this->cloneDateTime($from->getInvoicedFrom()));
        $to->setInvoicedTo($this->cloneDateTime($from->getInvoicedTo()));
        $to->setService($from->getService());
        $to->setOriginalService($from->getOriginalService());

        return $to;
    }

    private function convertItemSurcharge(FinancialItemSurchargeInterface $from): InvoiceItemSurcharge
    {
        $to = new InvoiceItemSurcharge();
        $this->setBaseData($from, $to);
        $to->setServiceSurcharge($from->getServiceSurcharge());
        $to->setService($from->getService());

        return $to;
    }

    private function setBaseData(FinancialItemInterface $from, FinancialItemInterface $to): void
    {
        $to->setTax1($from->getTax1());
        $to->setTax2($from->getTax2());
        $to->setTax3($from->getTax3());
        $to->setLabel($from->getLabel());
        $to->setQuantity($from->getQuantity());
        $to->setPrice($from->getPrice());
        $to->setTotal($from->getTotal());
        $to->setTaxable($from->getTaxable());
    }

    private function cloneDateTime(?\DateTime $dateTime): ?\DateTime
    {
        if (! $dateTime) {
            return null;
        }

        return clone $dateTime;
    }
}
