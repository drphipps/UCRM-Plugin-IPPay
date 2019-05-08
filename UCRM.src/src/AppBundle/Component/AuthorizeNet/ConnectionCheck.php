<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\AuthorizeNet;

use net\authorize\api\contract\v1\AuthenticateTestRequest;
use net\authorize\api\contract\v1\AuthenticateTestResponse;
use net\authorize\api\controller\AuthenticateTestController;

class ConnectionCheck extends AuthorizeNetAPIAccess
{
    public function check(): void
    {
        try {
            $request = new AuthenticateTestRequest();
            $request->setMerchantAuthentication($this->getMerchantAuthentication());
            $controller = new AuthenticateTestController($request);
            $response = $this->executeWithApiResponse($controller);
            if (
                ! $response instanceof AuthenticateTestResponse
                || $response->getMessages()->getResultCode() !== self::RESPONSE_OK
            ) {
                throw $this->createException($response);
            }
        } catch (\Exception $exception) {
            if ($exception instanceof AuthorizeNetException) {
                throw $exception;
            }

            throw new AuthorizeNetException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
