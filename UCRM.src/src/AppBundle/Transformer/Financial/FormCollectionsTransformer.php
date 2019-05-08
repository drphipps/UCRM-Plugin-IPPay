<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Transformer\Financial;

use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\FinancialItemFeeInterface;
use AppBundle\Entity\Financial\FinancialItemInterface;
use AppBundle\Entity\Financial\FinancialItemOtherInterface;
use AppBundle\Entity\Financial\FinancialItemProductInterface;
use AppBundle\Entity\Financial\FinancialItemServiceInterface;
use AppBundle\Entity\Financial\FinancialItemSurchargeInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItem;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItem;
use AppBundle\Entity\Product;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceSurcharge;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class FormCollectionsTransformer
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return ArrayCollection|FinancialItemInterface[]
     */
    public function getItemsFromFormData(FormInterface $form, FinancialInterface $financial): ArrayCollection
    {
        $items = new ArrayCollection();

        foreach ($form->get('financialItemServices')->getData() as $key => $itemService) {
            if ($this->prepareItemService($form->get('financialItemServices')->get($key), $itemService, $financial)) {
                $items->add($itemService);
            }
        }

        foreach ($form->get('financialItemSurcharges')->getData() as $key => $itemSurcharge) {
            if ($this->prepareItemSurcharge(
                $form->get('financialItemSurcharges')->get($key),
                $itemSurcharge,
                $financial
            )) {
                $items->add($itemSurcharge);
            }
        }

        foreach ($form->get('financialItemProducts')->getData() as $key => $itemProduct) {
            if ($this->prepareItemProduct($form->get('financialItemProducts')->get($key), $itemProduct, $financial)) {
                $items->add($itemProduct);
            }
        }

        foreach ($form->get('financialItemOthers')->getData() as $key => $itemOther) {
            $this->prepareItemOther($form->get('financialItemOthers')->get($key), $itemOther, $financial);
            $items->add($itemOther);
        }

        foreach ($form->get('financialItemFees')->getData() as $key => $itemFee) {
            if ($this->prepareItemFee($form->get('financialItemFees')->get($key), $itemFee, $financial)) {
                $items->add($itemFee);
            }
        }

        return $items;
    }

    public function fillFormDataWithSavedItems(FormInterface $form, FinancialInterface $financial): void
    {
        $financialItemServices = [];
        $financialItemSurcharges = [];
        $financialItemProducts = [];
        $financialItemOthers = [];
        $financialItemFees = [];

        foreach ($financial->getItems() as $item) {
            switch (true) {
                case $item instanceof FinancialItemServiceInterface:
                    $financialItemServices[] = $item;
                    break;
                case $item instanceof FinancialItemSurchargeInterface:
                    $financialItemSurcharges[] = $item;
                    break;
                case $item instanceof FinancialItemProductInterface:
                    $financialItemProducts[] = $item;
                    break;
                case $item instanceof FinancialItemOtherInterface:
                    $financialItemOthers[] = $item;
                    break;
                case $item instanceof FinancialItemFeeInterface:
                    $financialItemFees[] = $item;
                    break;
                default:
                    throw new \InvalidArgumentException('Unsupported item type.');
            }
        }

        $form->get('financialItemServices')->setData($financialItemServices);
        $form->get('financialItemSurcharges')->setData($financialItemSurcharges);
        $form->get('financialItemProducts')->setData($financialItemProducts);
        $form->get('financialItemOthers')->setData($financialItemOthers);
        $form->get('financialItemFees')->setData($financialItemFees);

        foreach ($financialItemServices as $key => $item) {
            $formItem = $form->get('financialItemServices')->get($key);
            $formItem->get('service')->setData(
                $item->getService() ? $item->getService()->getId() : null
            );
            $formItem->get('tax')->setData([$item->getTax1(), $item->getTax2(), $item->getTax3()]);
        }

        foreach ($financialItemSurcharges as $key => $item) {
            if (! $item->getServiceSurcharge()) {
                continue;
            }

            /** @var Service $service */
            $service = $item->getService();
            do {
                $serviceId = $service->getId();
            } while ($service = $service->getSupersededByService());
            $formItem = $form->get('financialItemSurcharges')->get((string) $key);
            $formItem->get('service')->setData($serviceId);
            $formItem->get('tax')->setData([$item->getTax1(), $item->getTax2(), $item->getTax3()]);
            $formItem->get('surcharge')->setData(
                $item->getServiceSurcharge() ? $item->getServiceSurcharge()->getId() : null
            );
        }

        foreach ($financialItemProducts as $key => $item) {
            $formItem = $form->get('financialItemProducts')->get((string) $key);
            $formItem->get('product')->setData(
                $item->getProduct() ? $item->getProduct()->getId() : null
            );
            $formItem->get('tax')->setData([$item->getTax1(), $item->getTax2(), $item->getTax3()]);
        }

        foreach ($financialItemOthers as $key => $item) {
            $formItem = $form->get('financialItemOthers')->get((string) $key);
            $formItem->get('tax')->setData([$item->getTax1(), $item->getTax2(), $item->getTax3()]);
        }

        foreach ($financialItemFees as $key => $item) {
            $formItem = $form->get('financialItemFees')->get((string) $key);
            $formItem->get('fee')->setData(
                $item->getFee() ? $item->getFee()->getId() : null
            );
            $formItem->get('tax')->setData([$item->getTax1(), $item->getTax2(), $item->getTax3()]);
        }
    }

    private function prepareItemService(
        FormInterface $field,
        FinancialItemServiceInterface $item,
        FinancialInterface $financial
    ): bool {
        $service = $this->entityManager->find(Service::class, $field->get('service')->getData());
        if (! $service) {
            return false;
        }

        $realService = $service;
        while (
            $realService->getStatus() === Service::STATUS_OBSOLETE
            && $realService->getSupersededByService()
        ) {
            $realService = $realService->getSupersededByService();
        }

        if ($realService !== $service) {
            $item->setService($realService);
            $item->setOriginalService($service);
        } else {
            $item->setService($service);
        }

        $this->setItemTaxes($item, $field->get('tax')->getData());

        if (
            $item->getInvoicedFrom()
            && $item->getInvoicedTo()
            && $item->getInvoicedFrom() > $item->getInvoicedTo()
        ) {
            $field->get('invoicedFrom')->addError(new FormError('Start date can\'t be greater than end date.'));
        }

        $this->setItemFinancial($item, $financial);

        return true;
    }

    private function prepareItemSurcharge(
        FormInterface $field,
        FinancialItemSurchargeInterface $item,
        FinancialInterface $financial
    ): bool {
        $service = $this->entityManager->find(Service::class, $field->get('service')->getData());
        $surcharge = $this->entityManager->find(ServiceSurcharge::class, $field->get('surcharge')->getData());

        if (! $service || ! $surcharge) {
            return false;
        }

        $this->setItemFinancial($item, $financial);
        $item->setService($service);
        $item->setServiceSurcharge($surcharge);

        $this->setItemTaxes($item, $field->get('tax')->getData());

        return true;
    }

    private function prepareItemProduct(
        FormInterface $field,
        FinancialItemProductInterface $item,
        FinancialInterface $financial
    ): bool {
        $product = $this->entityManager->find(Product::class, $field->get('product')->getData());
        if (! $product) {
            return false;
        }

        $this->setItemFinancial($item, $financial);
        $item->setProduct($product);

        $this->setItemTaxes($item, $field->get('tax')->getData());

        return true;
    }

    private function prepareItemOther(
        FormInterface $field,
        FinancialItemOtherInterface $item,
        FinancialInterface $financial
    ): void {
        $this->setItemFinancial($item, $financial);

        $this->setItemTaxes($item, $field->get('tax')->getData());
    }

    private function prepareItemFee(
        FormInterface $field,
        FinancialItemFeeInterface $item,
        FinancialInterface $financial
    ): bool {
        $fee = $this->entityManager->find(Fee::class, $field->get('fee')->getData());
        if (! $fee) {
            return false;
        }

        $this->setItemFinancial($item, $financial);
        $item->setFee($fee);

        $this->setItemTaxes($item, $field->get('tax')->getData());

        return true;
    }

    /**
     * @param array|ArrayCollection|null $taxes
     */
    private function setItemTaxes(FinancialItemInterface $item, $taxes): void
    {
        // for some reason, $taxes array can be indexed as "0,1,3" instead of "0,1,2", so we have to normalize it
        if (! is_array($taxes)) {
            if ($taxes instanceof ArrayCollection) {
                $taxes = $taxes->toArray();
            } else {
                $taxes = (array) $taxes;
            }
        }
        $taxes = array_values($taxes);

        $item->setTax1($taxes[0] ?? null);
        $item->setTax2($taxes[1] ?? null);
        $item->setTax3($taxes[2] ?? null);
    }

    private function setItemFinancial(FinancialItemInterface $item, FinancialInterface $financial): void
    {
        if ($item instanceof InvoiceItem && $financial instanceof Invoice) {
            $item->setInvoice($financial);
        } elseif ($item instanceof QuoteItem && $financial instanceof Quote) {
            $item->setQuote($financial);
        }
    }
}
