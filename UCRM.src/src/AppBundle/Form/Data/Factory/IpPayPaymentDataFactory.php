<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Factory;

use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Form\Data\IpPayPaymentData;

class IpPayPaymentDataFactory
{
    public function createFromClient(Client $client): IpPayPaymentData
    {
        $ipPayPayment = new IpPayPaymentData();
        if ($client->isInvoiceAddressSameAsContact()) {
            $ipPayPayment->address = $client->getStreet1() . ' ' . $client->getStreet2();
            $ipPayPayment->city = $client->getCity();
            $ipPayPayment->state = $client->getState()
                ? $client->getState()->getName()
                : null;
            $ipPayPayment->country = $client->getCountry();
            $ipPayPayment->zipCode = $client->getZipCode();
        } else {
            $ipPayPayment->address = $client->getInvoiceStreet1() . ' ' . $client->getInvoiceStreet2();
            $ipPayPayment->city = $client->getInvoiceCity();
            $ipPayPayment->state = $client->getInvoiceState()
                ? $client->getInvoiceState()->getName()
                : null;
            $ipPayPayment->country = $client->getInvoiceCountry();
            $ipPayPayment->zipCode = $client->getInvoiceZipCode();
        }

        return $ipPayPayment;
    }

    public function createFromInvoice(Invoice $invoice): IpPayPaymentData
    {
        $ipPayPayment = new IpPayPaymentData();
        if ($invoice->getClientInvoiceAddressSameAsContact()) {
            $ipPayPayment->address = $invoice->getClientStreet1() . ' ' . $invoice->getClientStreet2();
            $ipPayPayment->city = $invoice->getClientCity();
            $ipPayPayment->state = $invoice->getClientState()
                ? $invoice->getClientState()->getName()
                : null;
            $ipPayPayment->country = $invoice->getClientCountry();
            $ipPayPayment->zipCode = $invoice->getClientZipCode();
        } else {
            $ipPayPayment->address = $invoice->getClientInvoiceStreet1() . ' ' . $invoice->getClientInvoiceStreet2();
            $ipPayPayment->city = $invoice->getClientInvoiceCity();
            $ipPayPayment->state = $invoice->getClientInvoiceState()
                ? $invoice->getClientInvoiceState()->getName()
                : null;
            $ipPayPayment->country = $invoice->getClientInvoiceCountry();
            $ipPayPayment->zipCode = $invoice->getClientInvoiceZipCode();
        }

        return $ipPayPayment;
    }
}
