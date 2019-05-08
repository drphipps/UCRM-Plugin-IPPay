<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\PaymentPlan;

use AppBundle\Entity\Client;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractPaymentPlanEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var PaymentPlan
     */
    protected $paymentPlan;

    /**
     * @var Client|null
     */
    protected $client;

    public function __construct(PaymentPlan $paymentPlan)
    {
        $this->paymentPlan = $paymentPlan;
        $this->client = $paymentPlan->getClient();
    }

    public function getPaymentPlan(): PaymentPlan
    {
        return $this->paymentPlan;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function getWebhookEntityClass(): string
    {
        return 'subscription';
    }

    /**
     * @return PaymentPlan
     */
    public function getWebhookEntity(): ?object
    {
        return $this->paymentPlan;
    }

    /**
     * @return PaymentPlan|null
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->paymentPlan->getId();
    }
}
