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
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class UnlinkPaymentPlanConsumer extends AbstractConsumer
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
        return UnlinkPaymentPlanMessage::class;
    }

    public function executeBody(array $data): int
    {
        $paymentPlan = $this->entityManager->find(PaymentPlan::class, $data['paymentPlanId']);
        if (! $paymentPlan) {
            $this->logger->info(sprintf('Payment plan ID %d does not exist.', $data['paymentPlanId']));

            return self::MSG_REJECT;
        }

        if (! $paymentPlan->isLinked() || ! $paymentPlan->isActive()) {
            $this->logger->info(sprintf('Payment plan ID %d already unlinked or inactive.', $data['paymentPlanId']));

            return self::MSG_REJECT;
        }

        $this->paymentPlanFacade->handleUnlink($paymentPlan);

        return self::MSG_ACK;
    }
}
