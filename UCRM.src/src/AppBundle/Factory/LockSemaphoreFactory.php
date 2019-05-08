<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\Store\SemaphoreStore;

class LockSemaphoreFactory
{
    public function create(string $resource): Lock
    {
        return (new Factory(new SemaphoreStore()))->createLock($resource);
    }
}
