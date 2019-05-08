<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Exception;

interface FlashMessageExceptionInterface extends \Throwable
{
    public function setParameters(array $param): void;

    public function getParameters(): array;
}
