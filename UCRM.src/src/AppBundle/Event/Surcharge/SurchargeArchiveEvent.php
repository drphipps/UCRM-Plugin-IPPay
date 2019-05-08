<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Surcharge;

use AppBundle\Entity\Surcharge;
use Symfony\Component\EventDispatcher\Event;

class SurchargeArchiveEvent extends Event
{
    /**
     * @var Surcharge
     */
    private $surcharge;

    public function __construct(Surcharge $surcharge)
    {
        $this->surcharge = $surcharge;
    }

    public function getSurcharge(): Surcharge
    {
        return $this->surcharge;
    }
}
