<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\IpPay;

class IpPayRequestFactory
{
    public function create()
    {
        return [
            'TransactionType' => null,
            'TerminalID' => null,
            'TransactionID' => null,
            'Approval' => null,
            'RoutingCode' => null,
            'Origin' => null,
            'Password' => null,
            'OrderNumber' => null,
            'CardNum' => null,
            'CVV2' => null,
            'Token' => null,
            'Issue' => null,
            'CardExpMonth' => null,
            'CardExpYear' => null,
            'CardStartMonth' => null,
            'CardStartYear' => null,
            'Track1' => null,
            'Track2' => null,
            'AccountType' => null,
            'SEC' => null,
            'AccountNumber' => null,
            'ABA' => null,
            'CheckNumber' => null,
            'Scrutiny' => null,
            'CardName' => null,
            'DispositionType' => null,
            'TotalAmount' => null,
            'FeeAmount' => null,
            'TaxAmount' => null,
            'BillingAddress' => null,
            'BillingCity' => null,
            'BillingStateProv' => null,
            'BillingPostalCode' => null,
            'BillingCountry' => null,
            'BillingPhone' => null,
            'Email' => null,
            'UserIPAddress' => null,
            'UserHost' => null,
            'UDField1' => null,
            'UDField2' => null,
            'UDField3' => null,
            'ActionCode' => null,
            'IndustryType' => null,
            'VerificationType' => null,
            'CAVV' => null,
            'XID' => null,
            'ECI' => null,
            'CustomerPO' => null,
            'ShippingMethod' => null,
            'ShippingName' => null,
            'Address' => null,
            'City' => null,
            'StateProv' => null,
            'Country' => null,
            'Phone' => null,
        ];
    }
}
