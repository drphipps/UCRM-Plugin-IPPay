<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Surcharge;

use AppBundle\Entity\Surcharge;
use Symfony\Component\EventDispatcher\Event;

class SurchargeEditEvent extends Event
{
    /**
     * @var Surcharge
     */
    private $surcharge;

    /**
     * @var Surcharge
     */
    private $surchargeBeforeUpdate;

    public function __construct(Surcharge $surcharge, Surcharge $surchargeBeforeUpdate)
    {
        $this->surcharge = $surcharge;
        $this->surchargeBeforeUpdate = $surchargeBeforeUpdate;
    }

    public function getSurcharge(): Surcharge
    {
        return $this->surcharge;
    }

    public function getSurchargeBeforeUpdate(): Surcharge
    {
        return $this->surchargeBeforeUpdate;
    }
}
