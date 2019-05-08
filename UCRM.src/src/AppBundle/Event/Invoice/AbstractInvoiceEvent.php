<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractInvoiceEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var Invoice
     */
    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function getWebhookEntityClass(): string
    {
        return 'invoice';
    }

    /**
     * @return Invoice
     */
    public function getWebhookEntity(): ?object
    {
        return $this->invoice;
    }

    /**
     * @return Invoice|null
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->invoice->getId();
    }
}
