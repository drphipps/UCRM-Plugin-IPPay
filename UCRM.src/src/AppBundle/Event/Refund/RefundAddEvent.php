<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Refund;

use AppBundle\Entity\Refund;
use Symfony\Component\EventDispatcher\Event;

class RefundAddEvent extends Event
{
    /**
     * @var Refund
     */
    private $refund;

    public function __construct(Refund $refund)
    {
        $this->refund = $refund;
    }

    public function getRefund(): Refund
    {
        return $this->refund;
    }
}
