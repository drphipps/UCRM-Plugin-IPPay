<?php

namespace Tests\Unit;

use AppBundle\Component\AuthorizeNet\AuthorizeNetAPIAccess;
use Eloquent\Phony\Mock\Handle\InstanceHandle;
use Eloquent\Phony\Phpunit\Phony;
use net\authorize\api\contract\v1 as AnetAPI;

class AuthorizeNetAPIAccessTestCase extends UnitTestCase
{
    protected function mockApiResponse(InstanceHandle $handle, array $config)
    {
        foreach ($config as $row) {
            $handle
                ->executeWithApiResponse
                ->with(
                    $this->callback(
                        function ($object) use ($row) {
                            return $object instanceof $row['with'];
                        }
                    )
                )
                ->returns($row['returns']);
        }
    }

    protected function createApiResponseSuccess(
        string $class,
        string $returnMethod = null,
        $returnValue = null
    ): AnetAPI\ANetApiResponseType {
        $responseHandle = Phony::mock($class);
        $messages = new AnetAPI\MessagesType();

        $messages->setResultCode(AuthorizeNetAPIAccess::RESPONSE_OK);
        $responseHandle->getMessages->returns($messages);
        if ($returnMethod) {
            $responseHandle->{$returnMethod}->returns($returnValue);
        }

        return $responseHandle->get();
    }

    protected function createApiResponseFailure(
        string $class,
        string $errorCode,
        string $errorMessage = ''
    ): AnetAPI\ANetApiResponseType {
        $responseHandle = Phony::mock($class);
        $messages = new AnetAPI\MessagesType();

        $messages->setResultCode(AuthorizeNetAPIAccess::RESPONSE_ERROR);
        $messages->setMessage(
            [
                (new AnetAPI\MessagesType\MessageAType())
                    ->setCode($errorCode)
                    ->setText($errorMessage),
            ]
        );
        $responseHandle->getMessages->returns($messages);

        return $responseHandle->get();
    }
}
