<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial\ItemData;

use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Factory\Financial\FinancialItemServiceFactory;
use AppBundle\Service\Options;
use AppBundle\Service\ServiceCalculations;
use AppBundle\Util\Formatter;
use AppBundle\Util\Invoicing;

class ServiceItemDataFactory
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var ServiceCalculations
     */
    private $serviceCalculations;

    /**
     * @var FinancialItemServiceFactory
     */
    private $financialItemServiceFactory;

    public function __construct(
        Formatter $formatter,
        Options $options,
        ServiceCalculations $serviceCalculations,
        FinancialItemServiceFactory $financialItemServiceFactory
    ) {
        $this->formatter = $formatter;
        $this->options = $options;
        $this->serviceCalculations = $serviceCalculations;
        $this->financialItemServiceFactory = $financialItemServiceFactory;
    }

    public function create(Service $service): ?array
    {
        $serviceItem = $this->financialItemServiceFactory->createDefaultInvoiceItem($service);
        if (! $serviceItem) {
            return null;
        }

        list($invoicedFromChoices, $invoicedToChoices) = Invoicing::getServiceInvoiceablePeriods(
            $service,
            $this->formatter
        );
        $invoicedFrom = $serviceItem->getInvoicedFrom();
        $invoicedTo = $serviceItem->getInvoicedTo();

        // This can happen in rare cases when an invoice is created, then deleted
        // (moving invoicingLastPeriodEnd) and then invoicingPeriodStartDay is changed.
        if (! array_key_exists($invoicedFrom->format('Y-m-d'), $invoicedFromChoices)) {
            $invoicedFromChoices[$invoicedFrom->format('Y-m-d')]
                = $this->formatter->formatDate($invoicedFrom, Formatter::DEFAULT, Formatter::NONE);
            ksort($invoicedFromChoices);
        }

        // Not sure if something similar can happen for invoicedTo but better be safe than sorry.
        if (! array_key_exists($invoicedTo->format('Y-m-d'), $invoicedToChoices)) {
            $invoicedToChoices[$invoicedTo->format('Y-m-d')]
                = $this->formatter->formatDate($invoicedTo, Formatter::DEFAULT, Formatter::NONE);
            ksort($invoicedToChoices);
        }

        return [
            'service' => [
                'id' => $service->getId(),
            ],
            'label' => htmlspecialchars($serviceItem->getLabel() ?? '', ENT_QUOTES),
            'price' => $serviceItem->getPrice(),
            'quantity' => round($serviceItem->getQuantity(), 6),
            'tax1' => $serviceItem->getTax1() ? $serviceItem->getTax1()->getId() : null,
            'tax2' => $serviceItem->getTax2() ? $serviceItem->getTax2()->getId() : null,
            'tax3' => $serviceItem->getTax3() ? $serviceItem->getTax3()->getId() : null,
            'total' => $serviceItem->getTotal(),
            'discountType' => $serviceItem->getDiscountType(),
            'discountInvoiceLabel' => htmlspecialchars($serviceItem->getDiscountInvoiceLabel() ?? '', ENT_QUOTES),
            'discountPrice' => $serviceItem->getDiscountPrice(),
            'discountQuantity' => round($serviceItem->getDiscountQuantity(), 6),
            'discountTotal' => $serviceItem->getDiscountTotal(),
            'discountValue' => $serviceItem->getDiscountValue(),
            'discountFrom' => $serviceItem->getDiscountFrom() ? $serviceItem->getDiscountFrom()->format('Y-m-d') : null,
            'discountTo' => $serviceItem->getDiscountTo() ? $serviceItem->getDiscountTo()->format('Y-m-d') : null,
            'discountFromFormatted' => $serviceItem->getDiscountFrom() ? $this->formatter->formatDate(
                $serviceItem->getDiscountFrom(),
                Formatter::DEFAULT,
                Formatter::NONE
            ) : null,
            'discountToFormatted' => $serviceItem->getDiscountTo() ? $this->formatter->formatDate(
                $serviceItem->getDiscountTo(),
                Formatter::DEFAULT,
                Formatter::NONE
            ) : null,
            'invoicedFrom' => $this->formatter->formatDate(
                $serviceItem->getInvoicedFrom(),
                Formatter::DEFAULT,
                Formatter::NONE
            ),
            'invoicedTo' => $this->formatter->formatDate(
                $serviceItem->getInvoicedTo(),
                Formatter::DEFAULT,
                Formatter::NONE
            ),
            'invoicedFromChoices' => $invoicedFromChoices,
            'invoicedToChoices' => $invoicedToChoices,
            'invoicedFromSelected' => $serviceItem->getInvoicedFrom()->format('Y-m-d'),
            'invoicedToSelected' => $serviceItem->getInvoicedTo()->format('Y-m-d'),
        ];
    }

    public function createUpdated(
        Service $service,
        \DateTimeInterface $invoicedFrom,
        \DateTimeInterface $invoicedTo
    ): array {
        $serviceQuantity = Invoicing::getPeriodQuantity(
            $invoicedFrom,
            $invoicedTo,
            $service->getTariffPeriodMonths(),
            $service->getInvoicingPeriodStartDay(),
            (int) $this->options->get(Option::BILLING_CYCLE_TYPE)
        );

        $discountFrom = $service->getDiscountFrom();
        $discountTo = $service->getDiscountTo();
        $discountFrom = $discountFrom === null || $discountFrom < $invoicedFrom ? $invoicedFrom : $discountFrom;
        $discountTo = $discountTo === null || $discountTo > $invoicedTo ? $invoicedTo : $discountTo;
        $discountQuantity = 0.0;
        if ($discountFrom <= $discountTo) {
            $discountQuantity = Invoicing::getPeriodQuantity(
                $discountFrom,
                $discountTo,
                $service->getTariffPeriodMonths(),
                $service->getInvoicingPeriodStartDay(),
                (int) $this->options->get(Option::BILLING_CYCLE_TYPE)
            );
        }

        return [
            'serviceQuantity' => $serviceQuantity,
            'discountQuantity' => $discountQuantity,
            'discountFrom' => $discountFrom ? $discountFrom->format('Y-m-d') : null,
            'discountTo' => $discountTo ? $discountTo->format('Y-m-d') : null,
            'discountFromFormatted' => $discountFrom ? $this->formatter->formatDate(
                $discountFrom,
                Formatter::DEFAULT,
                Formatter::NONE
            ) : null,
            'discountToFormatted' => $discountTo ? $this->formatter->formatDate(
                $discountTo,
                Formatter::DEFAULT,
                Formatter::NONE
            ) : null,
        ];
    }
}
