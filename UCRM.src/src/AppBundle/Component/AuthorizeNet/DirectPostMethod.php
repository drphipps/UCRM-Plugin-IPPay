<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\AuthorizeNet;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentAuthorizeNet;
use AppBundle\Entity\PaymentToken;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Service\PublicUrlGenerator;

class DirectPostMethod
{
    private const LIVE_URL = 'https://secure2.authorize.net/gateway/transact.dll';
    private const SANDBOX_URL = 'https://test.authorize.net/gateway/transact.dll';

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var bool
     */
    private $sandbox = false;

    /**
     * @var string
     */
    private $token;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        PaymentFacade $paymentFacade,
        PublicUrlGenerator $publicUrlGenerator,
        \Twig_Environment $twig
    ) {
        $this->paymentFacade = $paymentFacade;
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->twig = $twig;
    }

    /**
     * @return $this
     */
    public function setSandbox(bool $sandbox)
    {
        $this->sandbox = $sandbox;

        return $this;
    }

    /**
     * @return $this
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return $this
     */
    public function setToken(string $token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentForm(Invoice $invoice, string $amount)
    {
        $time = time();

        if ($signatureKey = $this->organization->getAnetSignatureKey($this->sandbox)) {
            $fingerprint = DirectPostMethodForm::getFingerprintCurrencySHA512(
                $this->organization->getAnetLoginId($this->sandbox),
                $signatureKey,
                $amount,
                $invoice->getId() . $time,
                (string) $time,
                $invoice->getCurrency()->getCode()
            );
        } elseif ($transactionKey = $this->organization->getAnetTransactionKey($this->sandbox)) {
            $fingerprint = DirectPostMethodForm::getFingerprintCurrencyMD5(
                $this->organization->getAnetLoginId($this->sandbox),
                $transactionKey,
                $amount,
                $invoice->getId() . $time,
                (string) $time,
                $invoice->getCurrency()->getCode()
            );
        }

        $email = $invoice->getClient()->getFirstBillingEmail() ?: '';

        $form = new DirectPostMethodForm(
            [
                'x_amount' => $amount,
                'x_fp_sequence' => $invoice->getId() . $time,
                'x_fp_hash' => $fingerprint ?? '',
                'x_fp_timestamp' => $time,
                'x_relay_response' => 'TRUE',
                'x_relay_always' => 'TRUE',
                'x_relay_url' => $this->publicUrlGenerator->generate(
                    'anet_relay',
                    [
                        'token' => $this->token,
                    ]
                ),
                'x_login' => $this->organization->getAnetLoginId($this->sandbox),
                'x_cust_id' => $invoice->getClient()->getId(),
                'x_currency_code' => $invoice->getCurrency()->getCode(),
                'x_email' => $email,
                'x_invoice_num' => $invoice->getInvoiceNumber(),
            ]
        );

        $hiddenFields = $form->getHiddenFieldString();
        $postUrl = $this->sandbox ? self::SANDBOX_URL : self::LIVE_URL;

        return $this->twig->render(
            'online_payment/anet/dpm_form.html.twig',
            [
                'hiddenFields' => $hiddenFields,
                'postUrl' => $postUrl,
            ]
        );
    }

    /**
     * Processes POST request from Authorize.Net. If payment is approved, payment cover is created,
     * otherwise error is shown.
     *
     *
     * @return string
     *
     * @throws \Exception
     */
    public function processRelay(PaymentToken $paymentToken)
    {
        $invoice = $paymentToken->getInvoice();

        $response = new SHA512AuthorizeNetSIM(
            $this->organization->getAnetLoginId($this->sandbox),
            $this->organization->getAnetHash($this->sandbox),
            $this->organization->getAnetSignatureKey($this->sandbox)
        );

        if ($response->isAuthorizeNet()) {
            if ($response->approved) {
                $amount = round((float) $response->amount, 2);

                $paymentAuthorizeNet = new PaymentAuthorizeNet();
                $paymentAuthorizeNet->setAnetId($response->transaction_id);
                $paymentAuthorizeNet->setOrganization($invoice->getOrganization());
                $paymentAuthorizeNet->setClient($invoice->getClient());
                $paymentAuthorizeNet->setAmount($amount);

                $payment = new Payment();
                $payment->setMethod(Payment::METHOD_AUTHORIZE_NET);
                $payment->setCreatedDate(new \DateTime());
                $payment->setAmount($amount);
                $payment->setClient($invoice->getClient());
                $payment->setCurrency($invoice->getCurrency());

                $this->paymentFacade->handleCreateOnlinePayment($payment, $paymentAuthorizeNet, $paymentToken);

                $redirectUrl = $this->publicUrlGenerator->generate('online_payment_success');
            } else {
                // declined
                $redirectUrl = $this->publicUrlGenerator->generate(
                    'online_payment_cancelled',
                    [
                        'token' => $paymentToken->getToken(),
                        'message' => sprintf(
                            '%s (code %s)',
                            $response->response_reason_text,
                            $response->response_reason_code
                        ),
                    ]
                );
            }

            return $this->twig->render(
                'online_payment/anet/relay_response_snippet.html.twig',
                [
                    'redirectUrl' => $redirectUrl,
                ]
            );
        }

        throw new AuthorizeNetException('Error - not AuthorizeNet', 401);
    }
}
