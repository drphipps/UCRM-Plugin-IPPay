<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Client;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Service\ServiceCalculations;
use Doctrine\ORM\EntityManagerInterface;

class ClientAverageMonthlyPaymentCalculator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ServiceCalculations
     */
    private $serviceCalculations;

    public function __construct(EntityManagerInterface $entityManager, ServiceCalculations $serviceCalculations)
    {
        $this->entityManager = $entityManager;
        $this->serviceCalculations = $serviceCalculations;
    }

    public function calculate(Client $client): void
    {
        $services = $client->getNotDeletedServices();

        $totalPrice = null;
        foreach ($services as $service) {
            $this->entityManager->refresh($service);
            $service->calculateStatus();

            if (in_array($service->getStatus(), Service::ACTIVE_STATUSES, true)) {
                // we want to return null in case there are no services adding to total price
                // although if there is free service (price 0) we want to return the 0
                $totalPrice = $totalPrice ?? 0.0;
                $totalPrice += $this->serviceCalculations->getTotalPrice($service) / $service->getTariffPeriodMonths();
            }
        }

        $client->setAverageMonthlyPayment($totalPrice);
    }
}
