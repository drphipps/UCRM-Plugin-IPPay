<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\AuthorizeNet;

use AppBundle\Entity\Client;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\DateTimeImmutableFactory;
use Doctrine\ORM\EntityManager;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Nette\Utils\Strings;

/**
 * Wrapper for Authorize.Net ARB API.
 *
 * @see http://developer.authorize.net/api/reference/features/recurring_billing.html
 * @see http://developer.authorize.net/api/reference/#recurring-billing
 */
class AutomatedRecurringBilling extends AuthorizeNetAPIAccess
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @throws AuthorizeNetException
     */
    public function createSubscription(Client $client, PaymentPlan $paymentPlan)
    {
        $subscription = new AnetAPI\ARBSubscriptionType();
        $subscription->setName(Strings::substring($paymentPlan->getName(), 0, 50));

        $interval = new AnetAPI\PaymentScheduleType\IntervalAType();
        $interval->setLength($paymentPlan->getPeriod());
        $interval->setUnit('months');

        $paymentSchedule = new AnetAPI\PaymentScheduleType();
        $paymentSchedule->setInterval($interval);

        $now = new \DateTimeImmutable();
        $startDate = $paymentPlan->getStartDate()
            ? DateTimeImmutableFactory::createFromInterface($paymentPlan->getStartDate())
            : clone $now;

        // If the date is midnight, move it to current time.
        if ($startDate->format('H:i:s') === '00:00:00') {
            $startDate = $startDate->setTime(
                (int) $now->format('G'),
                (int) $now->format('i'),
                (int) $now->format('s')
            );
        }
        if ($startDate->modify('midnight') < $now->modify('midnight')) {
            $startDate = clone $now;
        }

        $startDateInFuture = $now->format('Y-m-d') !== $startDate->format('Y-m-d');
        if (! $startDateInFuture) {
            // Authorize.Net servers are in Mountain Time
            $timezoneAuthorizeNet = new \DateTimeZone('America/Denver');
            $nowAuthorizeNet = new \DateTimeImmutable('now', $timezoneAuthorizeNet);

            // If the start date is today, but Authorize.Net already has different date,
            // we need to move start date to Authorize.Net's timezone to prevent crash.
            if ($now->format('Y-m-d') !== $nowAuthorizeNet->format('Y-m-d')) {
                $startDate = clone $nowAuthorizeNet;
            }
        }

        $paymentSchedule->setStartDate(DateTimeFactory::createFromInterface($startDate));
        $paymentSchedule->setTotalOccurrences(9999);
        $subscription->setPaymentSchedule($paymentSchedule);
        $subscription->setAmount(
            round(
                $paymentPlan->getAmountInSmallestUnit() / $paymentPlan->getSmallestUnitMultiplier(),
                (int) log10($paymentPlan->getSmallestUnitMultiplier())
            )
        );

        $profile = new AnetAPI\CustomerProfileIdType();
        $profile->setCustomerProfileId($client->getAnetCustomerProfileId());
        $profile->setCustomerPaymentProfileId($client->getAnetCustomerPaymentProfileId());
        $subscription->setProfile($profile);

        // This is set only to prevent Authorize.Net from declining "duplicate" subscriptions for one client.
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber(Strings::substring(sprintf('PP%d', $paymentPlan->getId()), 0, 20));
        $subscription->setOrder($order);

        $request = new AnetAPI\ARBCreateSubscriptionRequest();
        $request->setMerchantAuthentication($this->getMerchantAuthentication());
        $request->setSubscription($subscription);

        $controller = new AnetController\ARBCreateSubscriptionController($request);

        // Authorize.net has a short delay before the API can see new customer profile. If this occurs it returns
        // E00040 (Record not found). After a few seconds this error should go away.
        $response = $this->executeWithApiResponse($controller);
        $i = 1;
        while (
            $response instanceof AnetAPI\ARBCreateSubscriptionResponse
            && $response->getMessages()->getResultCode() === self::RESPONSE_ERROR
            && count($response->getMessages()->getMessage()) === 1
            && $response->getMessages()->getMessage()[0]->getCode() === self::ERROR_RECORD_NOT_FOUND
        ) {
            $response = $this->executeWithApiResponse($controller);

            if ($i >= 10) {
                break;
            }

            ++$i;
            sleep(2);
        }

        if (
            $response instanceof AnetAPI\ARBCreateSubscriptionResponse
            && $response->getMessages()->getResultCode() === self::RESPONSE_OK
        ) {
            $paymentPlan->setProviderSubscriptionId($response->getSubscriptionId());
            $paymentPlan->setStatus($startDateInFuture ? PaymentPlan::STATUS_PENDING : PaymentPlan::STATUS_ACTIVE);
            $paymentPlan->setActive(true);
            $this->em->flush();
        } else {
            throw $this->createException($response);
        }
    }

    /**
     * @throws AuthorizeNetException
     */
    public function cancelSubscription(string $subscriptionId)
    {
        $request = new AnetAPI\ARBCancelSubscriptionRequest();
        $request->setMerchantAuthentication($this->getMerchantAuthentication());
        $request->setSubscriptionId($subscriptionId);

        $controller = new AnetController\ARBCancelSubscriptionController($request);
        $response = $this->executeWithApiResponse($controller);

        if ($response->getMessages()->getResultCode() !== self::RESPONSE_OK) {
            throw $this->createException($response);
        }
    }

    /**
     * @throws AuthorizeNetException
     */
    public function deleteCustomerProfile(string $customerProfileId): void
    {
        $request = new AnetAPI\DeleteCustomerProfileRequest();
        $request->setMerchantAuthentication($this->getMerchantAuthentication());
        $request->setCustomerProfileId($customerProfileId);

        $controller = new AnetController\DeleteCustomerProfileController($request);
        $response = $this->executeWithApiResponse($controller);

        if ($response->getMessages()->getResultCode() !== self::RESPONSE_OK) {
            throw $this->createException($response);
        }
    }
}
