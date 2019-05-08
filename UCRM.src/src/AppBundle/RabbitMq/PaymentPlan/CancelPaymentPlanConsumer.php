<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\PaymentPlan;

use AppBundle\Entity\EntityLog;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\PaymentPlanFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\RabbitMq\Exception\RejectStopConsumerException;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CancelPaymentPlanConsumer extends AbstractConsumer
{
    /**
     * @var ActionLogger
     */
    private $actionLogger;

    /**
     * @var PaymentPlanFacade
     */
    private $paymentPlanFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        LoggerInterface $logger,
        ActionLogger $actionLogger,
        PaymentPlanFacade $paymentPlanFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->actionLogger = $actionLogger;
        $this->paymentPlanFacade = $paymentPlanFacade;
    }

    protected function getMessageClass(): string
    {
        return CancelPaymentPlanMessage::class;
    }

    public function executeBody(array $data): int
    {
        $paymentPlan = $this->entityManager->find(PaymentPlan::class, $data['paymentPlanId']);
        if (! $paymentPlan || ! $paymentPlan->isActive()) {
            $this->logger->info(sprintf('Payment plan ID %d already deleted or inactive.', $data['paymentPlanId']));

            return self::MSG_REJECT;
        }

        try {
            $this->paymentPlanFacade->cancelSubscription($paymentPlan);
            $logMessage['logMsg'] = [
                'message' => 'Subscription %s was canceled',
                'replacements' => $paymentPlan->getName(),
            ];

            $this->actionLogger->log(
                $logMessage,
                null,
                $paymentPlan->getClient(),
                EntityLog::PAYMENT_PLAN_CANCELED
            );
            $this->logger->info(sprintf('Payment plan ID %d cancelled.', $paymentPlan->getId()));
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('Payment plan ID %d could not be cancelled.', $paymentPlan->getId()));
            throw new RejectStopConsumerException();
        }

        return self::MSG_ACK;
    }
}
