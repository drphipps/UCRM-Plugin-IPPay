<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\PayPal;

class PayPalException extends \RuntimeException
{
    /**
     * @var array
     */
    private $errorData = [];

    public function getErrorData(): array
    {
        return $this->errorData;
    }

    public function setErrorData(array $errorData): void
    {
        $this->errorData = $errorData;
    }
}
