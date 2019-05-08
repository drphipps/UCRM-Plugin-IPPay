<?php
/*
 * @copyright Copyright (c) 2016 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Tariff;

use AppBundle\Entity\Tariff;
use Symfony\Component\EventDispatcher\Event;

class TariffEditEvent extends Event
{
    /**
     * @var Tariff
     */
    private $tariff;

    /**
     * @var Tariff
     */
    private $tariffBeforeUpdate;

    public function __construct(Tariff $tariff, Tariff $tariffBeforeUpdate)
    {
        $this->tariff = $tariff;
        $this->tariffBeforeUpdate = $tariffBeforeUpdate;
    }

    public function getTariff(): Tariff
    {
        return $this->tariff;
    }

    public function getTariffBeforeUpdate(): Tariff
    {
        return $this->tariffBeforeUpdate;
    }
}
