<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\IpPay;

use AppBundle\Component\IpPay\Exception\FailedPaymentException;
use AppBundle\Entity\General;
use AppBundle\Entity\Organization;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\PaymentToken;
use AppBundle\Form\Data\IpPayPaymentData;
use AppBundle\Service\Options;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use League\ISO3166\ISO3166;
use Nette\Utils\Json;
use Nette\Utils\Random;

class IpPayRequestSender
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var IpPayRequestFactory
     */
    private $ipPayRequestFactory;

    /**
     * @var IpPayRequestValidator
     */
    private $ipPayRequestValidator;

    /**
     * @var IpPayRequestToXmlConverter
     */
    private $ipPayRequestToXmlConverter;

    /**
     * @var Options
     */
    private $options;

    public function __construct(
        Client $client,
        IpPayRequestFactory $ipPayRequestFactory,
        IpPayRequestValidator $ipPayRequestValidator,
        IpPayRequestToXmlConverter $ipPayRequestToXmlConverter,
        Options $options
    ) {
        $this->client = $client;
        $this->ipPayRequestFactory = $ipPayRequestFactory;
        $this->ipPayRequestValidator = $ipPayRequestValidator;
        $this->ipPayRequestToXmlConverter = $ipPayRequestToXmlConverter;
        $this->options = $options;
    }

    /**
     * @throws FailedPaymentException
     */
    public function sendPingRequest(Organization $organization): array
    {
        $request = $this->createPingRequest($organization);

        return $this->sendRequest($request, $organization);
    }

    /**
     * @throws FailedPaymentException
     */
    public function sendPaymentRequest(IpPayPaymentData $ipPayPayment, PaymentToken $paymentToken): array
    {
        $request = $this->createPaymentRequest($ipPayPayment, $paymentToken);

        return $this->sendRequest($request, $paymentToken->getInvoice()->getOrganization());
    }

    /**
     * @throws FailedPaymentException
     */
    public function sendPaymentTokenRequest(IpPayPaymentData $ipPayPayment, PaymentPlan $paymentPlan): array
    {
        $request = $this->createPaymentTokenRequest($ipPayPayment, $paymentPlan);

        return $this->sendRequest($request, $paymentPlan->getClient()->getOrganization());
    }

    /**
     * @throws FailedPaymentException
     */
    public function sendTokenRequest(IpPayPaymentData $ipPayPayment, PaymentPlan $paymentPlan): array
    {
        $request = $this->createTokenRequest($ipPayPayment, $paymentPlan);

        return $this->sendRequest($request, $paymentPlan->getClient()->getOrganization());
    }

    /**
     * @throws FailedPaymentException
     */
    public function sendSubscriptionPaymentRequest(PaymentPlan $paymentPlan): array
    {
        $request = $this->createSubscriptionPaymentRequest($paymentPlan);

        return $this->sendRequest($request, $paymentPlan->getClient()->getOrganization());
    }

    /**
     * @throws FailedPaymentException
     */
    private function sendRequest(array $request, Organization $organization): array
    {
        $this->ipPayRequestValidator->validateRequest($request);

        try {
            $guzzleResponse = $this->client->request(
                'POST',
                $organization->getIpPayUrl($this->isSandbox()),
                [
                    'body' => $this->ipPayRequestToXmlConverter->buildXml($request),
                ]
            );
        } catch (ConnectException $exception) {
            throw new FailedPaymentException(
                'Could not connect to IPPay.',
                (string) 500
            );
        }

        if ($guzzleResponse->getStatusCode() !== 200) {
            throw new FailedPaymentException(
                'IPPay returned an unexpected HTTP code %code%.',
                (string) $guzzleResponse->getStatusCode()
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $disableEntityLoaderState = libxml_disable_entity_loader(true);
        $xmlResponse = simplexml_load_string($guzzleResponse->getBody()->getContents());
        libxml_disable_entity_loader($disableEntityLoaderState);

        if (! $xmlResponse || libxml_get_errors()) {
            throw new FailedPaymentException('IPPay did not return a valid XML.');
        }

        $response = Json::decode(Json::encode($xmlResponse), Json::FORCE_ARRAY);

        if (
            ! is_array($response)
            || ! array_key_exists('ActionCode', $response)
        ) {
            throw new FailedPaymentException('IPPay returned an invalid response.');
        }

        switch ($request['TransactionType']) {
            case 'TOKENIZE':
                $responseText = 'TOKENIZED';
                break;
            case 'PING':
                $responseText = 'PING';
                break;
            default:
                $responseText = 'APPROVED';
        }

        if (
            $response['ActionCode'] !== '000'
            || ! array_key_exists('ResponseText', $response)
            || $response['ResponseText'] !== $responseText
        ) {
            throw new FailedPaymentException(
                'IPPay did not accept the payment (error code: %code%).',
                (string) $response['ActionCode']
            );
        }

        if (
            (
                $request['TransactionType'] === 'TOKENIZE'
                || ($request['Tokenize'] ?? false)
            )
            && (
                ! array_key_exists('Token', $response)
                || $response['Token'] === ''
            )
        ) {
            throw new FailedPaymentException('IPPay did not return subscription token.');
        }

        return $response;
    }

    private function createPingRequest(Organization $organization): array
    {
        $request = $this->ipPayRequestFactory->create();

        $request['TransactionType'] = 'PING';
        $request['TerminalID'] = $organization->getIpPayTerminalId($this->isSandbox());

        return $request;
    }

    private function createPaymentRequest(IpPayPaymentData $payment, PaymentToken $paymentToken): array
    {
        $request = $this->createRequest($payment);

        $invoice = $paymentToken->getInvoice();
        $request['TransactionType'] = 'SALE';
        $request['TerminalID'] = $invoice->getOrganization()->getIpPayTerminalId($this->isSandbox());
        $request['TotalAmount'] =
            (string) (int) ($paymentToken->getAmount() * (10 ** $invoice->getCurrency()->getFractionDigits()));
        $request['OrderNumber'] = $invoice->getInvoiceNumber();

        return $request;
    }

    private function createPaymentTokenRequest(IpPayPaymentData $payment, PaymentPlan $paymentPlan): array
    {
        $request = $this->createRequest($payment);

        $request['TransactionType'] = 'SALE';
        $request['TerminalID'] = $paymentPlan->getClient()->getOrganization()->getIpPayTerminalId($this->isSandbox());
        $request['TotalAmount'] = (string) $paymentPlan->getAmountInSmallestUnit();
        $request['OrderNumber'] = $paymentPlan->getName();
        $request['Tokenize'] = true;

        return $request;
    }

    private function createTokenRequest(IpPayPaymentData $payment, PaymentPlan $paymentPlan): array
    {
        $request = $this->createRequest($payment);

        $request['TransactionType'] = 'TOKENIZE';
        $request['TerminalID'] = $paymentPlan->getClient()->getOrganization()->getIpPayTerminalId($this->isSandbox());
        $request['TotalAmount'] = (string) $paymentPlan->getAmountInSmallestUnit();
        $request['OrderNumber'] = $paymentPlan->getName();

        return $request;
    }

    private function createRequest(IpPayPaymentData $payment): array
    {
        $request = $this->ipPayRequestFactory->create();

        $request['CardNum'] = str_replace(' ', '', $payment->cardNumber);
        list($month, $year) = explode('/', $payment->cardExpiration, 2);
        $request['CardExpMonth'] = trim($month);
        $request['CardExpYear'] = trim($year);
        $request['CVV2'] = $payment->CVV2;
        if ($payment->address) {
            $request['BillingAddress'] = $payment->address;
        }
        if ($payment->city) {
            $request['BillingCity'] = $payment->city;
        }
        if ($payment->state) {
            $request['BillingStateProv'] = $payment->state;
        }
        if ($payment->zipCode) {
            $request['BillingPostalCode'] = $payment->zipCode;
        }
        if ($payment->country) {
            $request['BillingCountry'] = (new ISO3166())->alpha2($payment->country->getCode())['alpha3'];
        }
        $request['TransactionID'] = date('ymdhs') . Random::generate(8, 'A-Z0-9');

        return $request;
    }

    private function createSubscriptionPaymentRequest(PaymentPlan $paymentPlan): array
    {
        $request = $this->ipPayRequestFactory->create();

        $request['TransactionID'] = date('ymdhs') . Random::generate(8, 'A-Z0-9');
        $request['TransactionType'] = 'SALE';
        $request['TerminalID'] = $paymentPlan->getClient()->getOrganization()->getIpPayTerminalId($this->isSandbox());
        $request['TotalAmount'] = (string) $paymentPlan->getAmountInSmallestUnit();
        $request['Token'] = $paymentPlan->getProviderSubscriptionId();
        // This should help with transactions after the credit card is expired.
        $request['Origin'] = 'RECURRING';

        return $request;
    }

    private function isSandbox(): bool
    {
        return (bool) $this->options->getGeneral(General::SANDBOX_MODE);
    }
}
