<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\PayPal;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;

class ExpressCheckout
{
    const CREDIT_CARD = 'credit_card';
    const BANK = 'bank';
    const PAYPAL = 'paypal';
    const PAY_UPON_INVOICE = 'pay_upon_invoice';
    const CARRIER = 'carrier';
    const ALTERNATE_PAYMENT = 'alternate_payment';

    const INTENT_SALE = 'sale';

    /**
     * @var array
     */
    private $items = [];

    /**
     * @var string
     */
    private $returnUrl;

    /**
     * @var string
     */
    private $cancelUrl;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $intent = self::INTENT_SALE;

    /**
     * @var Payment
     */
    private $resultPayment;

    /**
     * @var string
     */
    private $paymentType;

    /**
     * @var ApiContext
     */
    private $apiContext;

    public function __construct(ApiContext $apiContext)
    {
        $this->apiContext = $apiContext;
    }

    public function setCurrency(string $currencyCode): ExpressCheckout
    {
        $this->currency = $currencyCode;

        return $this;
    }

    /**
     * @param float $price is without TAXes
     */
    public function addItem(string $name, string $sku, int $quantity, float $price, float $tax): ExpressCheckout
    {
        $this->items[] = (new Item())
            ->setName($name)
            ->setCurrency($this->currency)
            ->setQuantity((string) $quantity)
            ->setSku($sku)
            ->setPrice($price)
            ->setTax($tax);

        return $this;
    }

    public function setReturnUrl(string $url): ExpressCheckout
    {
        $this->returnUrl = $url;

        return $this;
    }

    public function setCancelUrl(string $url): ExpressCheckout
    {
        $this->cancelUrl = $url;

        return $this;
    }

    public function setIntent(string $intent): ExpressCheckout
    {
        $this->intent = $intent;

        return $this;
    }

    /**
     * @throws PayPalException
     */
    public function setPaymentType(string $type): ExpressCheckout
    {
        $types = [
            self::CREDIT_CARD,
            self::BANK,
            self::PAYPAL,
            self::PAY_UPON_INVOICE,
            self::CARRIER,
            self::ALTERNATE_PAYMENT,
        ];

        if (in_array($type, $types, true)) {
            $this->paymentType = $type;
        } else {
            $msg = sprintf('Payment type is incorrect, possible types are: %s', implode(', ', $types));
            throw new PayPalException($msg);
        }

        return $this;
    }

    /**
     * @throws PayPalException
     */
    public function createPayment(?string $description = null, float $shipping = 0.0): Payment
    {
        try {
            $payer = new Payer();
            $payer->setPaymentMethod($this->paymentType);

            $itemList = new ItemList();
            $itemList->setItems($this->items);

            $subTotal = $tax = 0;
            foreach ($this->items as $item) {
                $subTotal += $item->getPrice() * $item->getQuantity();
                $tax += $item->getTax();
            }

            $fractionDigits = Intl::getCurrencyBundle()->getFractionDigits($this->currency);
            $subTotal = number_format($subTotal, $fractionDigits, '.', '');
            $tax = number_format($tax, $fractionDigits, '.', '');
            $total = number_format($subTotal + $shipping + $tax, $fractionDigits, '.', '');

            $details = (new Details())
                ->setShipping(number_format($shipping, $fractionDigits, '.', ''))
                ->setSubtotal($subTotal)
                ->setTax($tax);

            $amount = (new Amount())
                ->setCurrency($this->currency)
                ->setTotal($total)
                ->setDetails($details);

            // Can't use setInvoiceNumber() as that would result in a DUPLICATE_REQUEST_ID error when the client
            // chooses to pay less than the invoice amount with first payment and later pay the rest.
            $transaction = (new Transaction())
                ->setAmount($amount)
                ->setItemList($itemList);

            if (null !== $description) {
                $transaction->setDescription($description);
            }

            $redirectUrls = (new RedirectUrls())
                ->setReturnUrl($this->returnUrl)
                ->setCancelUrl($this->cancelUrl);

            $payment = (new Payment())
                ->setIntent($this->intent)
                ->setPayer($payer)
                ->setRedirectUrls($redirectUrls)
                ->setTransactions([$transaction]);

            $payment->create($this->apiContext);

            return $payment;
        } catch (\Throwable $exception) {
            $payPalException = new PayPalException($exception->getMessage(), $exception->getCode(), $exception);
            if ($exception instanceof PayPalConnectionException) {
                try {
                    $errorData = Json::decode($exception->getData(), Json::FORCE_ARRAY);
                } catch (JsonException $exception) {
                    $errorData = [];
                }

                $payPalException->setErrorData($errorData);
            }

            throw $payPalException;
        }
    }

    /**
     * @param float|string $total
     * @param float|string $subTotal
     * @param float|string $tax
     * @param float|string $shipping
     *
     * @throws PayPalException
     */
    public function processPayment(Request $request, $total, $subTotal, $tax, $shipping = 0.0): Payment
    {
        try {
            $paymentId = $request->get('paymentId');
            $payerId = $request->get('PayerID');

            $payment = (new Payment())
                ->get($paymentId, $this->apiContext);

            $details = (new Details())
                ->setShipping($shipping)
                ->setTax($tax)
                ->setSubtotal($subTotal);

            $amount = (new Amount())
                ->setCurrency($this->currency)
                ->setTotal($total)
                ->setDetails($details);

            $transaction = (new Transaction())
                ->setAmount($amount);

            $execution = (new PaymentExecution())
                ->setPayerId($payerId)
                ->addTransaction($transaction);

            $this->resultPayment = $payment->execute($execution, $this->apiContext);

            return $payment;
        } catch (\Throwable $exception) {
            $payPalException = new PayPalException($exception->getMessage(), $exception->getCode(), $exception);
            if ($exception instanceof PayPalConnectionException) {
                try {
                    $errorData = Json::decode($exception->getData(), Json::FORCE_ARRAY);
                } catch (JsonException $exception) {
                    $errorData = [];
                }

                $payPalException->setErrorData($errorData);
            }

            throw $payPalException;
        }
    }

    /**
     * @return Payment
     */
    public function getResultPayment()
    {
        return $this->resultPayment;
    }
}
