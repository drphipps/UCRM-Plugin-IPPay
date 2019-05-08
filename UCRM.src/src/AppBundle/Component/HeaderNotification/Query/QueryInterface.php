<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\HeaderNotification\Query;

interface QueryInterface
{
    public function getQuery(): string;

    public function getParameters(): array;

    public function getParameterTypes(): array;
}
