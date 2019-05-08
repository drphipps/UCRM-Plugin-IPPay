<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\PaymentPlan;

use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\PaymentPlanFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\RabbitMq\Exception\RejectStopConsumerException;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class UpdatePaymentPlanAmountConsumer extends AbstractConsumer
{
    /**
     * @var PaymentPlanFacade
     */
    private $paymentPlanFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        LoggerInterface $logger,
        PaymentPlanFacade $paymentPlanFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->paymentPlanFacade = $paymentPlanFacade;
    }

    protected function getMessageClass(): string
    {
        return UpdatePaymentPlanAmountMessage::class;
    }

    public function executeBody(array $data): int
    {
        $paymentPlan = $this->entityManager->find(PaymentPlan::class, $data['paymentPlanId']);
        if (! $paymentPlan || ! $paymentPlan->isActive() || ! $paymentPlan->isLinked()) {
            $this->logger->info(
                sprintf(
                    'Payment plan ID %d is deleted, inactive or does not have autopay enabled.',
                    $data['paymentPlanId']
                )
            );

            return self::MSG_REJECT;
        }

        if ($paymentPlan->getAmountInSmallestUnit() === $data['newAmountInSmallestUnit']) {
            $this->logger->info(sprintf('Payment plan ID %d amount is already changed.', $data['paymentPlanId']));

            return self::MSG_REJECT;
        }

        if ($paymentPlan->getClient()->isDeleted()) {
            $this->logger->info(
                sprintf(
                    'Client ID %d is archived, change not supported. (Payment plan ID %d)',
                    $paymentPlan->getClient()->getId(),
                    $data['paymentPlanId']
                )
            );

            return self::MSG_REJECT;
        }

        if (! $this->paymentPlanFacade->updateAmount($paymentPlan, $data['newAmountInSmallestUnit'])) {
            $this->logger->error(sprintf('Payment plan ID %d amount could not be changed.', $paymentPlan->getId()));

            throw new RejectStopConsumerException();
        }

        $this->logger->info(sprintf('Payment plan ID %d amount changed.', $paymentPlan->getId()));

        return self::MSG_ACK;
    }
}
