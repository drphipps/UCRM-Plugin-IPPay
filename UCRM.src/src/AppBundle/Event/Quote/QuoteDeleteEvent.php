<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Quote;

use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\WebhookEvent;

final class QuoteDeleteEvent extends AbstractQuoteEvent
{
    /**
     * @var int|null
     */
    private $id;

    public function __construct(Quote $quote, ?int $id)
    {
        parent::__construct($quote);
        $this->id = $id;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->id;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::DELETE;
    }

    public function getEventName(): string
    {
        return 'quote.delete';
    }
}
