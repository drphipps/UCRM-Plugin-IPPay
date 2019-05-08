<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\AuthorizeNet;

class DirectPostMethodForm extends \AuthorizeNetSIM_Form
{
    /**
     * @deprecated will stop working soon (probably 2019-01-31, but not sure)
     */
    public static function getFingerprintCurrencyMD5(
        string $apiLoginId,
        string $transactionKey,
        string $amount,
        string $fpSequence,
        string $fpTimestamp,
        string $currencyCode
    ): string {
        return hash_hmac(
            'md5',
            implode('^', [$apiLoginId, $fpSequence, $fpTimestamp, $amount, $currencyCode]),
            $transactionKey
        );
    }

    public static function getFingerprintCurrencySHA512(
        string $apiLoginId,
        string $signatureKey,
        string $amount,
        string $fpSequence,
        string $fpTimestamp,
        string $currencyCode
    ) {
        return hash_hmac(
            'SHA512',
            implode('^', [$apiLoginId, $fpSequence, $fpTimestamp, $amount, $currencyCode]),
            hex2bin($signatureKey)
        );
    }
}
