<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial;

use AppBundle\Entity\Financial\FinancialItemServiceInterface;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Financial\QuoteItemService;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Service\Options;
use AppBundle\Service\ServiceCalculations;
use AppBundle\Util\Invoicing;
use Symfony\Component\Translation\TranslatorInterface;

class FinancialItemServiceFactory
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ServiceCalculations
     */
    private $serviceCalculations;

    public function __construct(
        Options $options,
        TranslatorInterface $translator,
        ServiceCalculations $serviceCalculations
    ) {
        $this->options = $options;
        $this->translator = $translator;
        $this->serviceCalculations = $serviceCalculations;
    }

    /**
     * Used on new invoice form when clicking "add service" button or when requested via URL parameter.
     * Invoiced from / to dates are pre-filled automatically to continue the invoicing line.
     */
    public function createDefaultInvoiceItem(Service $service): ?InvoiceItemService
    {
        $range = $this->getDefaultInvoicedRange($service);
        if (! $range) {
            return null;
        }

        return $this->createInvoiceItem($service, $range['invoicedFrom'], $range['invoicedTo']);
    }

    /**
     * Used on new quote form when clicking "add service" button or when requested via URL parameter.
     * Invoiced from / to dates are pre-filled automatically to continue the invoicing line.
     */
    public function createDefaultQuoteItem(Service $service): ?QuoteItemService
    {
        $range = $this->getDefaultInvoicedRange($service);
        if (! $range) {
            return null;
        }

        return $this->createQuoteItem($service, $range['invoicedFrom'], $range['invoicedTo']);
    }

    public function createInvoiceItem(
        Service $service,
        \DateTime $invoicedFrom,
        \DateTime $invoicedTo
    ): InvoiceItemService {
        $item = new InvoiceItemService();
        $this->setData($item, $service, $invoicedFrom, $invoicedTo);

        return $item;
    }

    public function createQuoteItem(
        Service $service,
        \DateTime $invoicedFrom,
        \DateTime $invoicedTo
    ): QuoteItemService {
        $item = new QuoteItemService();
        $this->setData($item, $service, $invoicedFrom, $invoicedTo);

        return $item;
    }

    private function getDefaultInvoicedRange(Service $service): ?array
    {
        list($invoicedFromChoices, $invoicedToChoices) = Invoicing::getServiceInvoiceablePeriods($service, null);
        if (! $invoicedFromChoices || ! $invoicedToChoices) {
            return null;
        }
        ['invoicedFrom' => $invoicedFrom, 'invoicedTo' => $invoicedTo]
            = Invoicing::getMaxInvoicedPeriodService($service, new \DateTime());

        // For services with no uninvoiced period choose the last one as default.
        if (! $invoicedFrom || ! $invoicedTo) {
            end($invoicedFromChoices);
            $invoicedFrom = new \DateTime(key($invoicedFromChoices));
            end($invoicedToChoices);
            $invoicedTo = new \DateTime(key($invoicedToChoices));
        }

        return [
            'invoicedFrom' => $invoicedFrom,
            'invoicedTo' => $invoicedTo,
        ];
    }

    private function setData(
        FinancialItemServiceInterface $item,
        Service $service,
        \DateTime $invoicedFrom,
        \DateTime $invoicedTo
    ): void {
        $realService = $service;
        while ($realService->getStatus() === Service::STATUS_OBSOLETE && $realService->getSupersededByService()) {
            $realService = $realService->getSupersededByService();
        }

        if ($realService !== $service) {
            $item->setService($realService);
            $item->setOriginalService($service);
        } else {
            $item->setService($service);
        }

        $item->setLabel($service->getInvoiceLabelForView());
        $item->setPrice($service->getPrice());

        if ($service->getTariff()->getTaxable() && $service->getTariff()->getTax()) {
            $item->setTaxable(true);
            $item->setTax1($service->getTariff()->getTax());
        } else {
            $item->setTaxable($service->hasTax());
            $item->setTax1($service->getTax1());
            $item->setTax2($service->getTax2());
            $item->setTax3($service->getTax3());
        }
        $item->setInvoicedFrom($invoicedFrom);
        $item->setInvoicedTo($invoicedTo);

        $this->computeItemServiceDiscount($item);

        $item->setQuantity(
            Invoicing::getPeriodQuantity(
                $item->getInvoicedFrom(),
                $item->getInvoicedTo(),
                $service->getTariffPeriodMonths(),
                $service->getInvoicingPeriodStartDay(),
                (int) $this->options->get(Option::BILLING_CYCLE_TYPE)
            )
        );
        $item->setTotal($item->getPrice() * $item->getQuantity());
    }

    private function computeItemServiceDiscount(FinancialItemServiceInterface $item): void
    {
        $service = $item->getOriginalService() ?: $item->getService();

        $defaultDiscountLabel = $this->options->get(Option::DISCOUNT_INVOICE_LABEL)
            ?: $this->translator->trans('Discount');

        $item->setDiscountType($service->getDiscountType());
        $item->setDiscountInvoiceLabel($service->getDiscountInvoiceLabel() ?: $defaultDiscountLabel);
        $item->setDiscountValue($service->getDiscountValue());
        $item->setDiscountFrom($service->getDiscountFrom());
        $item->setDiscountTo($service->getDiscountTo());

        $invoicedFrom = $item->getInvoicedFrom();
        $invoicedTo = $item->getInvoicedTo();
        $discountFrom = $service->getDiscountFrom();
        $discountTo = $service->getDiscountTo();
        $discountFrom = $discountFrom === null || $discountFrom < $invoicedFrom ? $invoicedFrom : $discountFrom;
        $discountTo = $discountTo === null || $discountTo > $invoicedTo ? $invoicedTo : $discountTo;
        $discountPrice = $service->getDiscountPriceSinglePeriod();
        $discountTotal = $this->serviceCalculations->getDiscountPrice($service, $invoicedFrom, $invoicedTo);
        $discountQuantity = $this->serviceCalculations->getDiscountQuantity($service, $invoicedFrom, $invoicedTo);

        $item->setDiscountFrom($discountFrom);
        $item->setDiscountTo($discountTo);
        $item->setDiscountQuantity($discountQuantity);
        $item->setDiscountPrice($discountPrice * -1);
        $item->setDiscountTotal($discountTotal * -1);
    }
}
