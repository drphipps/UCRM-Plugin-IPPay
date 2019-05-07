<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\IpPay;

use AppBundle\Component\IpPay\Exception\InvalidIpPayRequestException;

class IpPayRequestValidator
{
    public function validateRequest(array $request): void
    {
        $this->requireField($request, 'TerminalID', 'TerminalID required for ALL transactions.');
        $this->requireField($request, 'TransactionType', 'No TransactionType supplied.');

        switch ($request['TransactionType']) {
            case 'PING':
                // Nothing else needed for PING request.
                return;

            case 'VOID':
                $this->requireField($request, 'Approval', 'No Approval Code supplied with VOID Transaction.');
                $this->requireField($request, 'CardNum', 'Card Number required with all VOID Transactions.');
                break;

            case 'FORCE':
                $this->requireField($request, 'Approval', 'No Approval Code supplied with FORCE Transaction.');
                // no break
            case 'SALE':
            case 'AUTHONLY':
            case 'CREDIT':
            case 'TOKENIZE':
                if (! isset($request['Token'])) {
                    $this->requireField($request, 'CardNum', 'CardNum required with SALE, AUTHONLY, CREDIT, and FORCE Transactions.');
                    $this->requireField($request, 'CardExpMonth', 'CardExpMonth required with SALE, AUTHONLY, CREDIT, and FORCE Transactions.');
                    $this->requireField($request, 'CardExpYear', 'CardExpYear required with SALE, AUTHONLY, CREDIT, and FORCE Transactions.');
                }
                break;

            case 'CHECK':
            case 'REVERSAL':
            case 'VOIDACH':
                $this->requireField($request, 'CardName', 'Customer Name is required for all CHECK, REVERSAL, VOIDACH transactions.');
                $this->requireField($request, 'AccountType', 'Account Type is required for all CHECK, REVERSAL, VOIDACH transactions.');
                $this->requireField($request, 'SEC', 'SEC Code is required for all CHECK, REVERSAL, VOIDACH transactions.');
                $this->requireField($request, 'AccountNumber', 'Account Number is required for all CHECK, REVERSAL, VOIDACH transactions.');
                $this->requireField($request, 'ABA', 'ABA Number is required for all CHECK, REVERSAL, VOIDACH transactions.');
                $this->requireField($request, 'CheckNumber', 'Check Number is required for all CHECK, REVERSAL, VOIDACH transactions.');
                break;

            default:
                throw new InvalidIpPayRequestException(sprintf('Unknown TransactionType "%s".', $request['TransactionType']));
        }

        $this->requireField($request, 'TransactionID', 'TransactionID required for ALL transactions.');
    }

    private function requireField(array $request, string $field, string $error): void
    {
        if (! isset($request[$field])) {
            throw new InvalidIpPayRequestException($error);
        }
    }
}
