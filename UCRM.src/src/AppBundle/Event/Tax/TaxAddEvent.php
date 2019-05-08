<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Tax;

use AppBundle\Entity\Tax;
use Symfony\Component\EventDispatcher\Event;

class TaxAddEvent extends Event
{
    /**
     * @var Tax
     */
    private $tax;

    /**
     * @var Tax
     */
    private $supersededTax;

    public function __construct(Tax $tax, ?Tax $supersededTax = null)
    {
        $this->tax = $tax;
        $this->supersededTax = $supersededTax;
    }

    public function getTax(): Tax
    {
        return $this->tax;
    }

    public function getSupersededTax(): ?Tax
    {
        return $this->supersededTax;
    }
}
