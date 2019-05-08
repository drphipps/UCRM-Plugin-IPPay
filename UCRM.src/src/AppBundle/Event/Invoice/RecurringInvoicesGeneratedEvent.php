<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Invoice;

use AppBundle\Entity\Financial\Invoice;
use Symfony\Component\EventDispatcher\Event;

class RecurringInvoicesGeneratedEvent extends Event
{
    /**
     * @var Invoice[]
     */
    private $approvedDrafts;

    /**
     * @var Invoice[]
     */
    private $createdDrafts;

    public function __construct(array $approvedDrafts, array $createdDrafts)
    {
        $this->approvedDrafts = $approvedDrafts;
        $this->createdDrafts = $createdDrafts;
    }

    /**
     * @return Invoice[]
     */
    public function getApprovedDrafts(): array
    {
        return $this->approvedDrafts;
    }

    /**
     * @return Invoice[]
     */
    public function getCreatedDrafts(): array
    {
        return $this->createdDrafts;
    }
}
