<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\IpPay;

class IpPayRequestToXmlConverter
{
    public function buildXml(array $request): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><ippay></ippay>');
        $this->addElement($xml, $request, 'TransactionType');
        $this->addElement($xml, $request, 'TerminalID');
        $this->addElement($xml, $request, 'TransactionID');
        $this->addElement($xml, $request, 'Approval');
        $this->addElement($xml, $request, 'RoutingCode');
        $this->addElement($xml, $request, 'Origin');
        $this->addElement($xml, $request, 'Password');
        $this->addElement($xml, $request, 'OrderNumber');

        $element = $this->addElement($xml, $request, 'CardNum');
        if ($element && ($request['Tokenize'] ?? false)) {
            $element->addAttribute('Tokenize', 'true');
        }

        $this->addElement($xml, $request, 'CVV2');
        $this->addElement($xml, $request, 'Token');
        $this->addElement($xml, $request, 'Issue');
        $this->addElement($xml, $request, 'CardExpMonth');
        $this->addElement($xml, $request, 'CardExpYear');
        $this->addElement($xml, $request, 'CardStartMonth');
        $this->addElement($xml, $request, 'CardStartYear');
        $this->addElement($xml, $request, 'Track1');
        $this->addElement($xml, $request, 'Track2');

        if (in_array($request['TransactionType'], ['CHECK', 'REVERSAL'], true)) {
            $ach = $xml->addChild('ACH');
            $ach->addAttribute('Type', $request['AccountType']);
            if ($request['SEC'] !== null) {
                $ach->addAttribute('SEC', $request['SEC']);
            }
            $this->addElement($ach, $request, 'AccountNumber');
            $this->addElement($ach, $request, 'ABA');
            $this->addElement($ach, $request, 'CheckNumber');
        }

        $this->addElement($xml, $request, 'Scrutiny');
        $this->addElement($xml, $request, 'CardName');
        $this->addElement($xml, $request, 'DispositionType');
        $this->addElement($xml, $request, 'TotalAmount');
        $this->addElement($xml, $request, 'FeeAmount');
        $this->addElement($xml, $request, 'TaxAmount');
        $this->addElement($xml, $request, 'BillingAddress');
        $this->addElement($xml, $request, 'BillingCity');
        $this->addElement($xml, $request, 'BillingStateProv');
        $this->addElement($xml, $request, 'BillingPostalCode');
        $this->addElement($xml, $request, 'BillingCountry');
        $this->addElement($xml, $request, 'BillingPhone');
        $this->addElement($xml, $request, 'Email');
        $this->addElement($xml, $request, 'UserIPAddress');
        $this->addElement($xml, $request, 'UserHost');
        $this->addElement($xml, $request, 'UDField1');
        $this->addElement($xml, $request, 'UDField2');
        $this->addElement($xml, $request, 'UDField3');
        $this->addElement($xml, $request, 'ActionCode');

        if ($request['IndustryType'] !== null) {
            $industryInfo = $xml->addChild('IndustryInfo');
            $industryInfo->addAttribute('Type', $request['IndustryType']);
        }

        if ($request['VerificationType'] !== null) {
            $verification = $xml->addChild('Verification');
            $verification->addAttribute('Type', $request['VerificationType']);
            $this->addElement($verification, $request, 'CAVV');
            $this->addElement($verification, $request, 'XID');
            $this->addElement($verification, $request, 'ECI');
        }

        $shippingInfo = $xml->addChild('ShippingInfo');
        $this->addElement($shippingInfo, $request, 'CustomerPO');
        $this->addElement($shippingInfo, $request, 'ShippingMethod');
        $this->addElement($shippingInfo, $request, 'ShippingName');

        $shippingAddress = $shippingInfo->addChild('ShippingAddr');
        $this->addElement($shippingAddress, $request, 'Address');
        $this->addElement($shippingAddress, $request, 'City');
        $this->addElement($shippingAddress, $request, 'StateProv');
        $this->addElement($shippingAddress, $request, 'Country');
        $this->addElement($shippingAddress, $request, 'Phone');

        return $xml->asXML();
    }

    private function addElement(\SimpleXMLElement $xml, array $request, string $field): ?\SimpleXMLElement
    {
        if ($request[$field] !== null) {
            return $xml->addChild($field, htmlspecialchars($request[$field]));
        }

        return null;
    }
}
