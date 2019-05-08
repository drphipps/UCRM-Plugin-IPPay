<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe;

use AppBundle\Component\Stripe\Exception\StripeEventIgnoredException;
use AppBundle\Component\Stripe\Exception\StripePaymentIgnoredException;
use AppBundle\Component\Stripe\Exception\TestWebhookException;
use AppBundle\Component\Stripe\Webhook\ChargeSucceededEventHandler;
use AppBundle\Component\Stripe\Webhook\CustomerDeletedEventHandler;
use AppBundle\Component\Stripe\Webhook\CustomerSubscriptionDeletedEventHandler;
use AppBundle\Entity\Organization;
use AppBundle\Facade\ClientBankAccountFacade;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Stripe\Error\InvalidRequest;
use Stripe\Event;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;

class StripeWebhookHandler
{
    private const TEST_WEBHOOK_ID = 'evt_00000000000000';
    private const TEST_WEBHOOK_ID_SUFFIX = '_00000000000000';

    public const EVENT_CHARGE_SUCCEEDED = 'charge.succeeded';
    public const EVENT_CUSTOMER_DELETED = 'customer.deleted';
    public const EVENT_CUSTOMER_SUBSCRIPTION_DELETED = 'customer.subscription.deleted';
    public const EVENT_CUSTOMER_BANK_ACCOUNT_DELETED = 'customer.bank_account.deleted';

    /**
     * @var ClientBankAccountFacade
     */
    private $clientBankAccountFacade;

    /**
     * @var ChargeSucceededEventHandler
     */
    private $chargeSucceededEventHandler;

    /**
     * @var CustomerDeletedEventHandler
     */
    private $customerDeletedEventHandler;

    /**
     * @var CustomerSubscriptionDeletedEventHandler
     */
    private $customerSubscriptionDeletedEventHandler;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var bool
     */
    private $sandbox;

    public function __construct(
        ClientBankAccountFacade $clientBankAccountFacade,
        ChargeSucceededEventHandler $chargeSucceededEventHandler,
        CustomerDeletedEventHandler $customerDeletedEventHandler,
        CustomerSubscriptionDeletedEventHandler $customerSubscriptionDeletedEventHandler,
        Organization $organization,
        bool $sandbox
    ) {
        $this->clientBankAccountFacade = $clientBankAccountFacade;
        $this->chargeSucceededEventHandler = $chargeSucceededEventHandler;
        $this->customerDeletedEventHandler = $customerDeletedEventHandler;
        $this->customerSubscriptionDeletedEventHandler = $customerSubscriptionDeletedEventHandler;
        $this->organization = $organization;
        $this->sandbox = $sandbox;
    }

    /**
     * Takes request from Stripe webhook API, verifies it against Stripe server and processes it.
     * Processes new payments and customer subscribe cancellations.
     *
     * @throws StripePaymentIgnoredException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws InvalidRequest
     * @throws JsonException
     * @throws TestWebhookException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ErrorException
     */
    public function process(Request $request): void
    {
        Stripe::setApiKey($this->organization->getStripeSecretKey($this->sandbox));

        $event = Json::decode($request->getContent());
        if (! ($event->id ?? false)) {
            throw new InvalidArgumentException('Wrong format of request');
        }

        try {
            $event = Event::retrieve($event->id);
        } catch (InvalidRequest $e) {
            if ($this->isTestWebhookId($event->id, $event->type ?? null)) {
                if ($this->sandbox) {
                    throw new TestWebhookException('Stripe test events are not processed.', Response::HTTP_OK);
                }

                throw new TestWebhookException(
                    'Test webhooks are not allowed in LIVE environment.',
                    Response::HTTP_FORBIDDEN,
                    $e
                );
            }

            throw $e;
        }

        if (! $event) {
            throw new AccessDeniedException('Event could not be retrieved from Stripe.');
        }

        if ($this->isTestWebhook($event->id, $event->type ?? null)) {
            throw new TestWebhookException('Stripe test events are not processed.', Response::HTTP_OK);
        }

        switch ($event->type) {
            case self::EVENT_CHARGE_SUCCEEDED:
                $this->chargeSucceededEventHandler->handle($event, $this->organization);

                break;
            case self::EVENT_CUSTOMER_DELETED:
                $this->customerDeletedEventHandler->handle($event);

                break;
            case self::EVENT_CUSTOMER_SUBSCRIPTION_DELETED:
                $this->customerSubscriptionDeletedEventHandler->handle($event);

                break;
            case self::EVENT_CUSTOMER_BANK_ACCOUNT_DELETED:
                $this->clientBankAccountFacade->disconnectStripeBankAccount($event->data->object->id);

                break;
            default:
                throw new StripeEventIgnoredException(
                    sprintf(
                        'Events of "%s" type are not handled by UCRM.',
                        $event->type
                    )
                );
        }
    }

    private function isTestWebhook(string $id, ?string $type): bool
    {
        return $this->sandbox && $this->isTestWebhookId($id, $type);
    }

    private function isTestWebhookId(string $id, ?string $type): bool
    {
        return $id === self::TEST_WEBHOOK_ID
            || $id === $type . self::TEST_WEBHOOK_ID_SUFFIX;
    }
}
