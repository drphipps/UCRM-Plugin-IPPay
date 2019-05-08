<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\PaymentPlan;

use AppBundle\Entity\Service;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use AppBundle\Service\ServiceCalculations;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RabbitMqBundle\RabbitMqEnqueuer;

class UpdatePaymentPlanWhenChangedConsumer extends AbstractConsumer
{
    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var ServiceCalculations
     */
    private $serviceCalculations;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        LoggerInterface $logger,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        ServiceCalculations $serviceCalculations
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->serviceCalculations = $serviceCalculations;
    }

    protected function getMessageClass(): string
    {
        return UpdatePaymentPlanWhenChangedMessage::class;
    }

    public function executeBody(array $data): int
    {
        $service = $this->entityManager->find(Service::class, $data['serviceId']);
        if (! $service || $service->isDeleted()) {
            $this->logger->warning(sprintf('Service ID %d not found or deleted.', $data['serviceId']));

            return self::MSG_REJECT;
        }

        $paymentPlans = $service->getActivePaymentPlans();
        if ($paymentPlans->isEmpty()) {
            $this->logger->warning(sprintf('Service ID %d has no active payment plans.', $data['serviceId']));

            return self::MSG_REJECT;
        }

        foreach ($paymentPlans as $paymentPlan) {
            if (! $paymentPlan->isActive() || ! $paymentPlan->isLinked()) {
                continue;
            }

            // If the service ended, cancel payment plan instead of changing the amount.
            if ($service->getStatus() === Service::STATUS_ENDED) {
                $this->rabbitMqEnqueuer->enqueue(new CancelPaymentPlanMessage($paymentPlan));

                continue;
            }

            if ($service->getTariffPeriodMonths() !== $paymentPlan->getPeriod()) {
                $this->rabbitMqEnqueuer->enqueue(new UnlinkPaymentPlanMessage($paymentPlan));

                continue;
            }

            $totalPrice = $this->serviceCalculations->getTotalPrice($service);
            $totalPriceInSmallestUnit = (int) round($totalPrice * $paymentPlan->getSmallestUnitMultiplier());

            if ($totalPriceInSmallestUnit !== $paymentPlan->getAmountInSmallestUnit()) {
                $this->rabbitMqEnqueuer->enqueue(
                    new UpdatePaymentPlanAmountMessage(
                        $paymentPlan,
                        $totalPriceInSmallestUnit
                    )
                );
            }
        }

        return self::MSG_ACK;
    }
}
