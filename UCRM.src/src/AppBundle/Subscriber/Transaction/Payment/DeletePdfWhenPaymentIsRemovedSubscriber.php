<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Payment;

use AppBundle\Entity\Payment;
use AppBundle\Event\Client\ClientDeleteEvent;
use AppBundle\Event\Payment\PaymentDeleteEvent;
use Ds\Queue;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class DeletePdfWhenPaymentIsRemovedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Queue|Payment[]
     */
    private $payments;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->payments = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentDeleteEvent::class => 'handlePaymentDeleteEvent',
            ClientDeleteEvent::class => 'handleClientDeleteEvent',
        ];
    }

    public function handlePaymentDeleteEvent(PaymentDeleteEvent $event): void
    {
        $this->payments->push($event->getPayment());
    }

    public function handleClientDeleteEvent(ClientDeleteEvent $event): void
    {
        foreach ($event->getClient()->getPayments() as $payment) {
            $this->payments->push($payment);
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

        foreach ($this->payments as $payment) {
            $path = $payment->getPdfPath();

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
        $this->payments->clear();
    }
}
