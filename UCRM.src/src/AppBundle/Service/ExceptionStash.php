<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

class ExceptionStash
{
    /**
     * @var \Throwable[]
     */
    private $exceptions = [];

    public function add(\Throwable $exception): void
    {
        $this->exceptions[$exception->getMessage()] = $exception;
    }

    /**
     * @return \Throwable[]
     */
    public function getAll(): array
    {
        $return = $this->exceptions;
        $this->exceptions = [];

        return $return;
    }
}
