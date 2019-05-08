<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Fee;

use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Service\Options;
use AppBundle\Util\Invoicing;

class EarlyTerminationDetector
{
    /**
     * @var Options
     */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function shouldCreateEarlyTerminationFee(
        Service $service,
        ?\DateTimeInterface $serviceActiveTo = null
    ): bool {
        $precision = 2;
        if (
            $service->getClient()
            && $service->getClient()->getOrganization()
            && $service->getClient()->getOrganization()->getCurrency()
        ) {
            $precision = $service->getClient()->getOrganization()->getCurrency()->getFractionDigits();
        }

        $earlyTerminationFeePrice = round(
            $service->getEarlyTerminationFeePrice() ?? 0,
            $precision
        );

        if (! $serviceActiveTo) {
            $serviceActiveTo = $service->getActiveTo();
        }

        if (
            ! $service->getEarlyTerminationFee()
            && $earlyTerminationFeePrice > 0
            && $service->getMinimumContractLengthMonths() !== null
            && ! $service->isDeleted()
            && ! $service->getClient()->isDeleted()
            && ! $service->getSupersededByService()
            && $serviceActiveTo
            && $service->getActiveFrom() <= $serviceActiveTo
        ) {
            return $this->getServiceLength($service, $serviceActiveTo) < $service->getMinimumContractLengthMonths();
        }

        return false;
    }

    private function getServiceLength(Service $service, \DateTimeInterface $activeTo): float
    {
        // For service after deferred change we want to use the start date from the original service. For reactivated
        // service we want to use the reactivation date. Because of this we need to use getActiveFrom and not
        // getInvoicingStart here. ActiveFrom is preserved after deferred change but reset when reactivating.
        // InvoicingStart is reset in both cases so service after deferred change would not be handled correctly.
        $quantity = Invoicing::getPeriodQuantity(
            $service->getActiveFrom(),
            $activeTo,
            $service->getTariffPeriodMonths(),
            $service->getInvoicingPeriodStartDay(),
            (int) $this->options->get(Option::BILLING_CYCLE_TYPE)
        );

        return $quantity * $service->getTariffPeriodMonths();
    }
}
