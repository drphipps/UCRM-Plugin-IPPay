<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Payment;

use AppBundle\Entity\Client;
use AppBundle\Entity\Payment;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractPaymentEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var Client|null
     */
    protected $client;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
        $this->client = $payment->getClient();
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function getWebhookEntityClass(): string
    {
        return 'payment';
    }

    /**
     * @return Payment
     */
    public function getWebhookEntity(): ?object
    {
        return $this->payment;
    }

    /**
     * @return Payment|null
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->payment->getId();
    }
}
