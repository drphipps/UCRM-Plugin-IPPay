<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\PayPal;

use AppBundle\Entity\Client;
use AppBundle\Entity\Currency;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentPayPal;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Facade\PaymentPlanFacade;
use AppBundle\Service\ActionLogger;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings;
use PayPal\Api\Sale;
use PayPal\Exception\PayPalConnectionException;
use Psr\Log\LoggerInterface;

class IPN
{
    const PROFILE_STATUS_ACTIVE = 'Active';

    const LIVE_URL = 'https://www.paypal.com/cgi-bin/webscr';
    const SANDBOX_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

    /**
     * @var ActionLogger
     */
    private $actionLogger;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $sandbox = false;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var PaymentPlanFacade
     */
    private $paymentPlanFacade;

    /**
     * @var ApiContextFactory
     */
    private $apiContextFactory;

    public function __construct(
        ActionLogger $actionLogger,
        EntityManager $em,
        LoggerInterface $logger,
        PaymentFacade $paymentFacade,
        PaymentPlanFacade $paymentPlanFacade,
        ApiContextFactory $apiContextFactory
    ) {
        $this->actionLogger = $actionLogger;
        $this->em = $em;
        $this->logger = $logger;
        $this->paymentFacade = $paymentFacade;
        $this->paymentPlanFacade = $paymentPlanFacade;
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
     * Verifies IPN request on PayPal servers and returns $_POST array when valid or false when invalid.
     *
     * @return array|bool
     */
    public function getVerifiedRequest()
    {
        // Read POST data
        // reading posted data directly from $_POST causes serialization
        // issues with array data in POST. Reading raw POST data from input stream instead.
        $rawPostData = file_get_contents('php://input');
        $rawPostArray = explode('&', $rawPostData);
        $myPost = [];
        foreach ($rawPostArray as $keyVal) {
            $keyVal = explode('=', $keyVal);
            if (count($keyVal) == 2) {
                $myPost[$keyVal[0]] = urldecode($keyVal[1]);
            }
        }

        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        foreach ($myPost as $key => $value) {
            $value = urlencode($value);
            $req .= "&$key=$value";
        }

        // Post IPN data back to PayPal to validate the IPN data is genuine
        // Without this step anyone can fake IPN data
        $ch = curl_init($this->sandbox ? self::SANDBOX_URL : self::LIVE_URL);
        if ($ch == false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);

        if ($this->sandbox) {
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        }

        // Set TCP timeout to 30 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);

        // Certificate from "http://curl.haxx.se/docs/caextract.html" - This is mandatory for some environments.
        $cert = __DIR__ . '/cacert.pem';
        curl_setopt($ch, CURLOPT_CAINFO, $cert);

        $res = curl_exec($ch);
        if (curl_errno($ch) != 0) { // cURL error
            if ($this->sandbox) {
                $this->logger->error('Can\'t connect to PayPal to validate IPN message: ' . curl_error($ch));
            }
            curl_close($ch);
            exit;
        }
        // Log the entire HTTP response if debug is switched on.
        if ($this->sandbox) {
            $this->logger->error(
                'HTTP request of validation request:' . curl_getinfo(
                    $ch,
                    CURLINFO_HEADER_OUT
                ) . " for IPN payload: $req"
            );
            $this->logger->error(
                'HTTP response of validation request: $res'
            );
        }
        curl_close($ch);

        // Inspect IPN validation result and act accordingly

        // Split response headers and payload, a better way for strcmp
        $tokens = explode("\r\n\r\n", trim($res));
        $res = trim(end($tokens));

        if (strcmp($res, 'VERIFIED') === 0) {
            if ($this->sandbox) {
                $this->logger->error("Verified IPN: $req ");
            }

            return $_POST;
        }
        if (strcmp($res, 'INVALID') === 0) {
            // log for manual investigation
            // Add business logic here which deals with invalid IPN messages
            if ($this->sandbox) {
                $this->logger->error("Invalid IPN: $req");
            }
        }

        return false;
    }

    /**
     * Processes verified IPN request and returns HTTP code to send back.
     *
     * @param array $data
     *
     * @return int
     *
     * @throws \Exception
     */
    public function processVerifiedRequest($data)
    {
        switch ($data['txn_type'] ?? '') {
            case 'recurring_payment':
                $subscriptionId = $data['recurring_payment_id'];
                $paymentPlan = $this->em->getRepository(PaymentPlan::class)->findOneBy(
                    [
                        'providerSubscriptionId' => $subscriptionId,
                        'provider' => PaymentPlan::PROVIDER_PAYPAL,
                        'active' => true,
                    ]
                );

                if (! $paymentPlan) {
                    return 404;
                }

                if ($data['profile_status'] !== self::PROFILE_STATUS_ACTIVE) {
                    $paymentPlan->setActive(false);
                    $paymentPlan->setCanceledDate(new \DateTime());
                    break;
                }

                $client = $paymentPlan->getClient();
                $saleId = $data['txn_id'];
                $paymentPayPal = $this->em->getRepository(PaymentPayPal::class)->findOneBy(
                    [
                        'payPalId' => $saleId,
                    ]
                );

                if ($paymentPayPal) {
                    break;
                }

                $sale = $this->getSale($client->getOrganization(), $saleId);
                if (! $sale) {
                    return 400;
                }

                $amount = (float) $sale->getAmount()->getTotal();

                $currency = $this->em->getRepository(Currency::class)->findOneBy(
                    [
                        'code' => Strings::upper($sale->getAmount()->getCurrency()),
                    ]
                );

                $paymentPayPal = new PaymentPayPal();
                $paymentPayPal->setPayPalId($saleId);
                $paymentPayPal->setState($sale->getState());
                $paymentPayPal->setOrganization($client->getOrganization());
                $paymentPayPal->setClient($client);
                $paymentPayPal->setType(PaymentPayPal::TYPE_SALE);
                $paymentPayPal->setAmount($amount);

                $payment = new Payment();
                $payment->setMethod(Payment::METHOD_PAYPAL_SUBSCRIPTION);
                $payment->setCreatedDate(new \DateTime());
                $payment->setAmount($amount);
                $payment->setNote($sale->getId());
                $payment->setClient($client);
                $payment->setCurrency($currency);

                $invoices = $this->em->getRepository(Invoice::class)->getClientUnpaidInvoicesWithCurrency(
                    $client,
                    $currency
                );

                $this->paymentFacade->handleCreate($payment, $invoices, $paymentPayPal);

                break;
            case 'recurring_payment_profile_created':
                $subscriptionId = $data['recurring_payment_id'];
                /** @var PaymentPlan $paymentPlan */
                $paymentPlan = $this->em->getRepository(PaymentPlan::class)->findOneBy(
                    [
                        'providerSubscriptionId' => $subscriptionId,
                        'provider' => PaymentPlan::PROVIDER_PAYPAL,
                    ]
                ); // intentionally no check for active

                if (! $paymentPlan) {
                    return 404;
                }

                if ($data['profile_status'] === 'Active') {
                    $paymentPlan->setStatus(PaymentPlan::STATUS_ACTIVE);
                } else {
                    $paymentPlan->setActive(false);
                    $paymentPlan->setCanceledDate(new \DateTime());
                    $paymentPlan->setStatus(PaymentPlan::STATUS_CANCELLED);
                }

                /** @var Client $client */
                $client = $this->em->merge($paymentPlan->getClient());
                $client->setPayPalCustomerId($data['payer_id']);

                if (($data['initial_payment_status'] ?? '') === 'Completed') {
                    $saleId = $data['initial_payment_txn_id'];
                    $paymentPayPal = $this->em->getRepository(PaymentPayPal::class)->findOneBy(
                        [
                            'payPalId' => $saleId,
                        ]
                    );

                    if (! $paymentPayPal) {
                        $sale = $this->getSale($client->getOrganization(), $saleId);
                        if (! $sale) {
                            return 400;
                        }

                        $amount = (float) $sale->getAmount()->getTotal();

                        /** @var Currency $currency */
                        $currency = $this->em->getRepository(Currency::class)->findOneBy(
                            [
                                'code' => Strings::upper($sale->getAmount()->getCurrency()),
                            ]
                        );

                        $paymentPayPal = new PaymentPayPal();
                        $paymentPayPal->setPayPalId($saleId);
                        $paymentPayPal->setState($sale->getState());
                        $paymentPayPal->setOrganization($client->getOrganization());
                        $paymentPayPal->setClient($client);
                        $paymentPayPal->setType(PaymentPayPal::TYPE_SALE);
                        $paymentPayPal->setAmount($amount);

                        $payment = new Payment();
                        $payment->setMethod(Payment::METHOD_PAYPAL_SUBSCRIPTION);
                        $payment->setCreatedDate(new \DateTime());
                        $payment->setAmount($amount);
                        $payment->setNote($sale->getId());
                        $payment->setClient($client);
                        $payment->setCurrency($currency);

                        $invoices = $this->em->getRepository(Invoice::class)->getClientUnpaidInvoicesWithCurrency(
                            $client,
                            $currency
                        );

                        $this->paymentFacade->handleCreate($payment, $invoices, $paymentPayPal);
                    }
                } elseif (! array_key_exists('initial_payment_status', $data)) {
                    // If the start date is in the future, set payment plan to pending.
                    try {
                        $nextPaymentDate = new \DateTime($data['next_payment_date'] ?? '@invalid');
                    } catch (\Exception $exception) {
                        $nextPaymentDate = null;
                    }

                    if (
                        $nextPaymentDate
                        && $paymentPlan->getStatus() === PaymentPlan::STATUS_ACTIVE
                        && $nextPaymentDate->format('Y-m-d') !== $paymentPlan->getCreatedDate()->format('Y-m-d')
                    ) {
                        $paymentPlan->setStatus(PaymentPlan::STATUS_PENDING);
                    }
                }

                break;
            case 'recurring_payment_profile_suspend':
            case 'recurring_payment_suspended_due_to_max_failed_payment':
                $subscriptionId = $data['recurring_payment_id'];
                $paymentPlan = $this->em->getRepository(PaymentPlan::class)->findOneBy(
                    [
                        'providerSubscriptionId' => $subscriptionId,
                        'provider' => PaymentPlan::PROVIDER_PAYPAL,
                        'active' => true,
                    ]
                );

                if (! $paymentPlan) {
                    return 200;
                }

                // @todo Suspend not supported (yet), cancel instead
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

                break;
            case 'recurring_payment_profile_cancel':
                $subscriptionId = $data['recurring_payment_id'];
                $paymentPlan = $this->em->getRepository(PaymentPlan::class)->findOneBy(
                    [
                        'providerSubscriptionId' => $subscriptionId,
                        'provider' => PaymentPlan::PROVIDER_PAYPAL,
                        'active' => true,
                    ]
                );

                if (! $paymentPlan) {
                    return 200;
                }

                $paymentPlan->setActive(false);
                $paymentPlan->setStatus(PaymentPlan::STATUS_CANCELLED);
                $paymentPlan->setCanceledDate(new \DateTime());

                break;
            case 'recurring_payment_expired':
            case 'recurring_payment_failed':
            case 'recurring_payment_skipped':
                $subscriptionId = $data['recurring_payment_id'];
                $paymentPlan = $this->em->getRepository(PaymentPlan::class)->findOneBy(
                    [
                        'providerSubscriptionId' => $subscriptionId,
                        'provider' => PaymentPlan::PROVIDER_PAYPAL,
                        'active' => true,
                    ]
                );

                if (! $paymentPlan) {
                    return 404;
                }

                if ($data['profile_status'] !== 'Active') {
                    $paymentPlan->setActive(false);
                    $paymentPlan->setStatus(PaymentPlan::STATUS_CANCELLED);
                    $paymentPlan->setCanceledDate(new \DateTime());
                }

                break;
            case '':
            default:
                return 200;
        }

        $this->em->flush();

        return 200;
    }

    /**
     * @return Sale|null
     */
    private function getSale(Organization $organization, string $saleId)
    {
        try {
            return Sale::get(
                $saleId,
                $this->apiContextFactory->create($organization, $this->sandbox)
            );
        } catch (PayPalConnectionException $e) {
            return null;
        }
    }
}
