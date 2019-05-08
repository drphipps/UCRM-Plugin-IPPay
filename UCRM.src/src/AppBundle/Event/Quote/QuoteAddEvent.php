<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Quote;

use AppBundle\Entity\WebhookEvent;

final class QuoteAddEvent extends AbstractQuoteEvent
{
    public function getWebhookChangeType(): string
    {
        return WebhookEvent::INSERT;
    }

    public function getEventName(): string
    {
        return 'quote.add';
    }
}
