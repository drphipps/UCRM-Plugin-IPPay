<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Component\Serializer;

use ApiBundle\Component\Validator\ValidationHttpException;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use Nette\Utils\Strings;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JsonExceptionNormalizer implements SubscribingHandlerInterface
{
    /**
     * @var bool
     */
    private $debug;

    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => \Exception::class,
                'method' => 'serializeToJson',
            ],
        ];
    }

    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
    }

    public function serializeToJson(
        JsonSerializationVisitor $visitor,
        \Exception $exception,
        array $type,
        Context $context
    ) {
        $data = $this->convertToArray($exception);

        return $visitor->visitArray($data, $type, $context);
    }

    private function convertToArray(\Exception $exception): array
    {
        $data = [];
        $data['code'] = $this->getCode($exception);
        $data['message'] = $this->getMessage($exception);

        if ($exception instanceof ValidationHttpException) {
            $data['errors'] = $exception->getValidationErrors();
        }

        if ($this->debug) {
            $data['debug'] = $this->getDebug($exception);
        }

        return $data;
    }

    private function getCode(\Exception $exception): int
    {
        if ($exception instanceof HttpException) {
            $code = $exception->getStatusCode();
        } elseif ($this->debug) {
            $code = $exception->getCode();
        } else {
            $code = 500;
        }

        return $code;
    }

    private function getMessage(\Exception $exception): string
    {
        if ($exception instanceof HttpException || $this->debug) {
            $message = $exception->getMessage();
            // Replace fully classified class names by short names
            $message = Strings::replace($message, "~(?:\w+\\\)+(\w+)~", '$1');
        } else {
            $message = 'Error occurred.';
        }

        return $message;
    }

    private function getDebug(\Exception $exception): array
    {
        return [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => explode(PHP_EOL, $exception->getTraceAsString()),
        ];
    }
}
