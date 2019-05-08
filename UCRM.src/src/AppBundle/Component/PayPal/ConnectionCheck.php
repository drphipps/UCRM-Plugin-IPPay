<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\PayPal;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PayPal\Api\Payment;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

class ConnectionCheck
{
    public function check(ApiContext $apiContext): void
    {
        try {
            Payment::all(
                [
                    'count' => 1,
                ],
                $apiContext
            );
        } catch (PayPalConnectionException $exception) {
            try {
                $data = Json::decode($exception->getData(), Json::FORCE_ARRAY);
            } catch (JsonException $jsonException) {
                throw new PayPalException($exception->getMessage(), $exception->getCode(), $exception);
            }
            $message = [];
            if (array_key_exists('error', $data)) {
                $message[] = $data['error'];
            }
            if (array_key_exists('error_description', $data)) {
                $message[] = $data['error_description'];
            }

            throw new PayPalException(
                $message ? implode(': ', $message) : $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        } catch (\Exception $exception) {
            throw new PayPalException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
