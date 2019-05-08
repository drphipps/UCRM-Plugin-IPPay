<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\IpPay;

use AppBundle\Component\IpPay\Exception\FailedPaymentException;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentIpPay;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\PaymentToken;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Form\Data\IpPayPaymentData;
use AppBundle\Service\ActionLogger;
use AppBundle\Util\DateTimeFactory;

class IpPayPaymentHandler
{
    /**
     * @var ActionLogger
     */
    private $actionLogger;

    /**
     * @var IpPayRequestSender
     */
    private $requestSender;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    public function __construct(
        ActionLogger $actionLogger,
        IpPayRequestSender $requestSender,
        PaymentFacade $paymentFacade
    ) {
        $this->actionLogger = $actionLogger;
        $this->requestSender = $requestSender;
        $this->paymentFacade = $paymentFacade;
    }

    /**
     * @throws FailedPaymentException
     */
    public function processPayment(IpPayPaymentData $ipPayPayment, PaymentToken $paymentToken): void
    {
        $response = $this->requestSender->sendPaymentRequest($ipPayPayment, $paymentToken);

        $paymentIpPay = new PaymentIpPay();
        $paymentIpPay->setTransactionId($response['TransactionID']);

        $payment = new Payment();
        $payment->setMethod(Payment::METHOD_IPPAY);
        $payment->setCreatedDate(new \DateTime());
        $payment->setAmount($paymentToken->getAmount());
        $payment->setClient($paymentToken->getInvoice()->getClient());
        $payment->setCurrency($paymentToken->getInvoice()->getCurrency());

        $this->paymentFacade->handleCreateOnlinePayment($payment, $paymentIpPay, $paymentToken);
    }

    /**
     * @throws FailedPaymentException
     */
    public function processSubscription(IpPayPaymentData $ipPayPayment, PaymentPlan $paymentPlan): void
    {
        $today = new \DateTimeImmutable('today midnight');

        if (
            $paymentPlan->getProvider() !== PaymentPlan::PROVIDER_IPPAY
            || $paymentPlan->getNextPaymentDate() !== null
            || $paymentPlan->isActive()
        ) {
            throw new \InvalidArgumentException();
        }

        if ((clone $paymentPlan->getStartDate())->modify('midnight') <= $today) {
            $response = $this->requestSender->sendPaymentTokenRequest($ipPayPayment, $paymentPlan);

            $paymentPlan->setProviderPlanId($response['TransactionID']);
            $paymentPlan->setProviderSubscriptionId($response['Token']);
            $paymentPlan->setActive(true);

            $paymentIpPay = new PaymentIpPay();
            $paymentIpPay->setTransactionId($response['TransactionID']);

            $nextPaymentDay = clone $paymentPlan->getStartDate();
            $nextPaymentDay->modify(sprintf('+%d months', $paymentPlan->getPeriod()));

            $this->saveNewSubscriptionPayment($response, $paymentPlan, $nextPaymentDay);
        } else {
            $response = $this->requestSender->sendTokenRequest($ipPayPayment, $paymentPlan);

            $paymentPlan->setProviderPlanId($response['TransactionID']);
            $paymentPlan->setProviderSubscriptionId($response['Token']);
            $paymentPlan->setActive(true);
            $paymentPlan->setStatus(PaymentPlan::STATUS_PENDING);
            $paymentPlan->setNextPaymentDate(clone $paymentPlan->getStartDate());

            $this->paymentFacade->handleSaveSubscription($paymentPlan);
        }
    }

    public function processNextPayment(PaymentPlan $paymentPlan): void
    {
        $today = new \DateTimeImmutable('today midnight');

        if (
            $paymentPlan->getProvider() !== PaymentPlan::PROVIDER_IPPAY
            || $paymentPlan->getNextPaymentDate() === null
            || ! $paymentPlan->isActive()
            || (clone $paymentPlan->getNextPaymentDate())->modify('midnight') > $today
        ) {
            throw new \InvalidArgumentException();
        }

        try {
            $response = $this->requestSender->sendSubscriptionPaymentRequest($paymentPlan);
        } catch (FailedPaymentException $e) {
            $paymentPlan->setNextPaymentDate(DateTimeFactory::createFromInterface($today->modify('+1 day')));
            $paymentPlan->setFailures($paymentPlan->getFailures() + 1);

            if ($paymentPlan->getFailures() >= 7) {
                $paymentPlan->setStatus(PaymentPlan::STATUS_ERROR);
                $paymentPlan->setActive(false);
                $message['logMsg'] = [
                    'message' => 'Subscription was automatically cancelled after 7 failed attempts.',
                    'replacements' => '',
                ];
                $this->actionLogger->log($message, null, $paymentPlan->getClient(), EntityLog::PAYMENT_PLAN_CANCELED);
            }

            $this->paymentFacade->handleSaveSubscription($paymentPlan);

            return;
        }

        $nextPaymentDay = clone $paymentPlan->getNextPaymentDate();
        $nextPaymentDay->modify(sprintf('+%d months', $paymentPlan->getPeriod()));
        $nextPaymentDay->modify(sprintf('-%d days', $paymentPlan->getFailures()));

        $this->saveNewSubscriptionPayment($response, $paymentPlan, $nextPaymentDay);
    }

    private function saveNewSubscriptionPayment(
        array $response,
        PaymentPlan $paymentPlan,
        \DateTime $nextPaymentDay
    ): void {
        $paymentIpPay = new PaymentIpPay();
        $paymentIpPay->setTransactionId($response['TransactionID']);

        $payment = new Payment();
        $payment->setMethod(Payment::METHOD_IPPAY_SUBSCRIPTION);
        $payment->setCreatedDate(new \DateTime());
        $payment->setAmount($paymentPlan->getAmountInSmallestUnit() / $paymentPlan->getSmallestUnitMultiplier());
        $payment->setClient($paymentPlan->getClient());
        $payment->setCurrency($paymentPlan->getCurrency());

        $paymentPlan->setNextPaymentDate($nextPaymentDay);
        $paymentPlan->setStatus(PaymentPlan::STATUS_ACTIVE);
        $paymentPlan->setFailures(0);

        $this->paymentFacade->handleCreateOnlinePaymentUsingSubscription($payment, $paymentIpPay);
    }
}
