<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Quote;

use AppBundle\Entity\Financial\Quote;
use AppBundle\Event\Client\ClientDeleteEvent;
use AppBundle\Event\Quote\QuoteDeleteEvent;
use Ds\Queue;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class DeletePdfWhenQuoteIsRemovedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Queue|Quote[]
     */
    private $quotes;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->quotes = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            QuoteDeleteEvent::class => 'handleQuoteDeleteEvent',
            ClientDeleteEvent::class => 'handleClientDeleteEvent',
        ];
    }

    public function handleQuoteDeleteEvent(QuoteDeleteEvent $event): void
    {
        $this->quotes->push($event->getQuote());
    }

    public function handleClientDeleteEvent(ClientDeleteEvent $event): void
    {
        foreach ($event->getClient()->getQuotes() as $quote) {
            $this->quotes->push($quote);
        }
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        $filesystem = new Filesystem();

        foreach ($this->quotes as $quote) {
            $path = $quote->getPdfPath();

            if (! $path) {
                continue;
            }

            try {
                $filesystem->remove($this->rootDir . $path);
            } catch (IOException $e) {
                // Silently ignore.
            }
        }
    }

    public function rollback(): void
    {
        $this->quotes->clear();
    }
}
