<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Quote;

use AppBundle\Entity\Financial\Quote;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractQuoteEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var Quote
     */
    protected $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }

    public function getWebhookEntityClass(): string
    {
        return 'quote';
    }

    /**
     * @return Quote
     */
    public function getWebhookEntity(): ?object
    {
        return $this->quote;
    }

    /**
     * @return Quote|null
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->quote->getId();
    }
}
