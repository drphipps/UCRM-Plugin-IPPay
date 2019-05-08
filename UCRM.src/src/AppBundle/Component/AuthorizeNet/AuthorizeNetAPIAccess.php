<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\AuthorizeNet;

use AppBundle\Entity\Organization;
use net\authorize\api\constants\ANetEnvironment;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller\base\ApiOperationBase;

abstract class AuthorizeNetAPIAccess
{
    public const ERROR_TERMINATED = 'E00038';
    public const ERROR_DUPLICATE_PROFILE = 'E00039';
    public const ERROR_RECORD_NOT_FOUND = 'E00040';

    public const RESPONSE_OK = 'Ok';
    public const RESPONSE_ERROR = 'Error';

    /**
     * @var Organization
     */
    protected $organization;

    /**
     * @var bool
     */
    protected $sandbox = false;

    public function setSandbox(bool $sandbox): AuthorizeNetAPIAccess
    {
        $this->sandbox = $sandbox;

        return $this;
    }

    public function setOrganization(Organization $organization): AuthorizeNetAPIAccess
    {
        $this->organization = $organization;

        return $this;
    }

    protected function getMerchantAuthentication(): AnetAPI\MerchantAuthenticationType
    {
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($this->organization->getAnetLoginId($this->sandbox));
        $merchantAuthentication->setTransactionKey($this->organization->getAnetTransactionKey($this->sandbox));

        return $merchantAuthentication;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function executeWithApiResponse(
        ApiOperationBase $controller
    ): AnetAPI\ANetApiResponseType {
        return $controller->executeWithApiResponse(
            $this->sandbox ? ANetEnvironment::SANDBOX : ANetEnvironment::PRODUCTION
        );
    }

    protected function createException(AnetAPI\ANetApiResponseType $response): AuthorizeNetException
    {
        $error = [];
        $messages = $response->getMessages()->getMessage();
        foreach ($messages as $message) {
            $error[] = sprintf('%s: %s', $message->getCode(), $message->getText());
        }

        $exception = new AuthorizeNetException(implode(PHP_EOL, $error));
        $exception->setMessages($messages);

        return $exception;
    }
}
