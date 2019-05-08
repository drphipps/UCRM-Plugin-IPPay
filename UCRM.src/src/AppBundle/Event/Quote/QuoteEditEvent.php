<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Quote;

use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\WebhookEvent;

final class QuoteEditEvent extends AbstractQuoteEvent
{
    /**
     * @var Quote
     */
    private $quoteBeforeUpdate;

    public function __construct(Quote $quote, Quote $quoteBeforeUpdate)
    {
        parent::__construct($quote);
        $this->quoteBeforeUpdate = $quoteBeforeUpdate;
    }

    public function getQuoteBeforeUpdate(): Quote
    {
        return $this->quoteBeforeUpdate;
    }

    /**
     * @return Quote
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return $this->getQuoteBeforeUpdate();
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::EDIT;
    }

    public function getEventName(): string
    {
        return 'quote.edit';
    }
}
