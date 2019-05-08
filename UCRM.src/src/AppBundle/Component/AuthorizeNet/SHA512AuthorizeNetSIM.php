<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\AuthorizeNet;

class SHA512AuthorizeNetSIM extends \AuthorizeNetSIM
{
    /**
     * @var string|null
     */
    private $signatureKey;

    public function __construct(?string $apiLoginId, ?string $md5HashKey, ?string $signatureKey)
    {
        parent::__construct($apiLoginId ?? '', $md5HashKey ?? '');

        $this->signatureKey = $signatureKey;
    }

    public function isAuthorizeNet(): bool
    {
        if (! $this->signatureKey) {
            return parent::isAuthorizeNet();
        }

        return hash_equals(
            $this->generateHash(),
            $this->response['x_SHA2_Hash'] ?? ''
        );
    }

    public function generateHash(): string
    {
        if (! $this->signatureKey) {
            return parent::generateHash();
        }

        $fields = [
            $this->response['x_trans_id'] ?? '',
            $this->response['x_test_request'] ?? '',
            $this->response['x_response_code'] ?? '',
            $this->response['x_auth_code'] ?? '',
            $this->response['x_cvv2_resp_code'] ?? '',
            $this->response['x_cavv_response'] ?? '',
            $this->response['x_avs_code'] ?? '',
            $this->response['x_method'] ?? '',
            $this->response['x_account_number'] ?? '',
            $this->response['x_amount'] ?? '',
            $this->response['x_company'] ?? '',
            $this->response['x_first_name'] ?? '',
            $this->response['x_last_name'] ?? '',
            $this->response['x_address'] ?? '',
            $this->response['x_city'] ?? '',
            $this->response['x_state'] ?? '',
            $this->response['x_zip'] ?? '',
            $this->response['x_country'] ?? '',
            $this->response['x_phone'] ?? '',
            $this->response['x_fax'] ?? '',
            $this->response['x_email'] ?? '',
            $this->response['x_ship_to_company'] ?? '',
            $this->response['x_ship_to_first_name'] ?? '',
            $this->response['x_ship_to_last_name'] ?? '',
            $this->response['x_ship_to_address'] ?? '',
            $this->response['x_ship_to_city'] ?? '',
            $this->response['x_ship_to_state'] ?? '',
            $this->response['x_ship_to_zip'] ?? '',
            $this->response['x_ship_to_country'] ?? '',
            $this->response['x_invoice_num'] ?? '',
        ];

        return strtoupper(
            hash_hmac(
                'sha512',
                '^' . implode('^', $fields) . '^',
                hex2bin($this->signatureKey)
            )
        );
    }
}
