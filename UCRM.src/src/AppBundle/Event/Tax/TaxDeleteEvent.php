<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Tax;

use AppBundle\Entity\Tax;
use Symfony\Component\EventDispatcher\Event;

class TaxDeleteEvent extends Event
{
    /**
     * @var Tax
     */
    private $tax;

    /**
     * @var int
     */
    private $id;

    public function __construct(Tax $tax)
    {
        $this->tax = $tax;
        $this->id = $tax->getId();
    }

    public function getTax(): Tax
    {
        return $this->tax;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
