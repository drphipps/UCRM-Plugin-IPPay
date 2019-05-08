<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\PayPal;

use AppBundle\Entity\PaymentPlan;
use AppBundle\Util\DateFormats;
use Doctrine\ORM\EntityManager;
use PayPal\Api\Agreement;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\Payer;
use PayPal\Api\PayerInfo;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Api\ShippingAddress;
use PayPal\Common\PayPalModel;

class Subscription
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ApiContextFactory
     */
    private $apiContextFactory;

    /**
     * @var PaymentPlan
     */
    private $paymentPlan;

    /**
     * amount in cents.
     *
     * @var int
     */
    private $amount;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $returnUrl;

    /**
     * @var string
     */
    private $cancelUrl;

    /**
     * @var bool
     */
    private $sandbox = false;

    public function __construct(EntityManager $em, ApiContextFactory $apiContextFactory)
    {
        $this->em = $em;
        $this->apiContextFactory = $apiContextFactory;
    }

    /**
     * @return self
     */
    public function setSandbox(bool $sandbox)
    {
        $this->sandbox = $sandbox;

        return $this;
    }

    /**
     * @param PaymentPlan $paymentPlan
     *
     * @return $this
     */
    public function setPaymentPlan($paymentPlan)
    {
        $this->paymentPlan = $paymentPlan;

        return $this;
    }

    /**
     * @param int $amount
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param string $returnUrl
     *
     * @return $this
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    /**
     * @param string $cancelUrl
     *
     * @return $this
     */
    public function setCancelUrl($cancelUrl)
    {
        $this->cancelUrl = $cancelUrl;

        return $this;
    }

    /**
     * Creates payment plan via PayPal API.
     *
     * Returns approval URL where client has to be redirected.
     */
    public function execute(): ?string
    {
        $client = $this->paymentPlan->getClient();
        $apiContext = $this->apiContextFactory->create($client->getOrganization(), $this->sandbox);

        $plan = new Plan();
        $plan->setName($this->paymentPlan->getName())
            ->setDescription($this->description)
            ->setType('INFINITE');

        $price = new Currency(
            [
                'value' => round(
                    $this->paymentPlan->getAmountInSmallestUnit() / $this->paymentPlan->getSmallestUnitMultiplier(),
                    (int) log10($this->paymentPlan->getSmallestUnitMultiplier())
                ),
                'currency' => $this->paymentPlan->getCurrency()->getCode(),
            ]
        );

        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName('Regular payments')
            ->setType('REGULAR')
            ->setFrequency('MONTH')
            ->setFrequencyInterval((string) $this->paymentPlan->getPeriod())
            ->setCycles('0')
            ->setAmount($price);

        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl($this->returnUrl)
            ->setCancelUrl($this->cancelUrl)
            ->setAutoBillAmount('no')
            ->setInitialFailAmountAction('CANCEL')
            ->setMaxFailAttempts('0');

        $trialEnd = null;
        $paymentPlanStartDate = $this->paymentPlan->getStartDate();
        $utcTimezone = new \DateTimeZone('UTC');
        if ($paymentPlanStartDate && (new \DateTime())->format('Y-m-d') !== $paymentPlanStartDate->format('Y-m-d')) {
            $trialEnd = clone $paymentPlanStartDate;
            $trialEnd->setTimezone($utcTimezone);
        }

        // If we're starting now set setup fee, otherwise the default is 0.
        if (! $trialEnd) {
            $merchantPreferences->setSetupFee($price);
        }

        $plan->setPaymentDefinitions([$paymentDefinition]);
        $plan->setMerchantPreferences($merchantPreferences);

        $createdPlan = $plan->create($apiContext);

        $patch = new Patch();
        $value = new PayPalModel('{"state":"ACTIVE"}');

        $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);
        $patchRequest = new PatchRequest();
        $patchRequest->addPatch($patch);

        $createdPlan->update($patchRequest, $apiContext);
        $activePlan = Plan::get($createdPlan->getId(), $apiContext);
        $this->paymentPlan->setProviderPlanId($activePlan->getId());
        $this->em->persist($this->paymentPlan);

        $agreement = new Agreement();
        $agreement->setName($this->description)
            ->setDescription($this->description);

        $startDate = $trialEnd ?: new \DateTime(sprintf('+%s months', $this->paymentPlan->getPeriod()), $utcTimezone);
        $agreement->setStartDate($startDate->format(DateFormats::ISO8601_Z));

        $shippingAddress = new ShippingAddress();
        $shippingAddress->setLine1($client->getStreet1())
            ->setCity($client->getCity())
            ->setPostalCode($client->getZipCode())
            ->setCountryCode($client->getCountry() ? $client->getCountry()->getCode() : '');
        if ($client->getStreet2()) {
            $shippingAddress->setLine2($client->getStreet2());
        }
        if ($client->getState()) {
            $shippingAddress->setState($client->getState()->getCode());
        }
        $agreement->setShippingAddress($shippingAddress);

        $plan = new Plan();
        $plan->setId($activePlan->getId());
        $agreement->setPlan($plan);

        $payer = new Payer();
        $payerInfo = new PayerInfo();
        $billingEmail = $client->getFirstBillingEmail();
        if ($billingEmail) {
            $payerInfo->setEmail($billingEmail);
        }

        if ($client->isCompany()) {
            $payerInfo->setFirstName($client->getCompanyName());
        } else {
            $payerInfo->setFirstName($client->getFirstName())
                ->setLastName($client->getLastName());
        }
        $payer->setPayerInfo($payerInfo);
        $payer->setPaymentMethod('paypal');
        $agreement->setPayer($payer);
        $agreement = $agreement->create($apiContext);
        $approvalUrl = $agreement->getApprovalLink();

        $this->em->flush();

        return $approvalUrl;
    }
}
