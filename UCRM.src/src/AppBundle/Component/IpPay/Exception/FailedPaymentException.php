<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\IpPay\Exception;

class FailedPaymentException extends \RuntimeException
{
    /**
     * @var string|null
     */
    private $errorCode;

    /**
     * @var string|null
     */
    private $errorMessage;

    public function __construct(string $message, ?string $errorCode = null, ?string $errorMessage = null)
    {
        parent::__construct($message);

        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
