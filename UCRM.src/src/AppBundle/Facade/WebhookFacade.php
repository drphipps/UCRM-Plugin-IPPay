<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\WebhookAddress;
use AppBundle\Entity\WebhookEvent;
use AppBundle\Event\Client\TestEvent;
use AppBundle\Service\Webhook\Requester;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class WebhookFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Requester
     */
    private $requester;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        Requester $requester,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->requester = $requester;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleCreate(WebhookAddress $webhookAddress): void
    {
        if (Helpers::isDemo()) {
            return;
        }

        $this->entityManager->persist($webhookAddress);
        $this->entityManager->flush();
    }

    public function handleUpdate(WebhookAddress $webhookAddress): void
    {
        if (Helpers::isDemo()) {
            return;
        }

        $this->entityManager->flush();
    }

    public function handleDelete(WebhookAddress $webhookAddress): void
    {
        $this->setDeleted($webhookAddress);
        $this->entityManager->flush();
    }

    /**
     * Sends a TestEvent via event listeners and thence via rabbitmq.
     */
    public function handleTestSend(WebhookAddress $webhookAddress): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($webhookAddress) {
                yield new TestEvent($webhookAddress->getId());
            }
        );
    }

    /**
     * Resends the given WebhookEvent directly, without looping through rabbitmq.
     */
    public function handleResend(WebhookEvent $webhookEvent): void
    {
        $this->requester->send($webhookEvent);
    }

    /**
     * @return array [$deleted, $failed]
     *
     * @throws \Exception
     */
    public function handleDeleteMultiple(array $ids): array
    {
        $webhookAddresses = $this->entityManager->getRepository(WebhookAddress::class)->findBy(
            [
                'id' => $ids,
                'deletedAt' => null,
            ]
        );

        $count = count($webhookAddresses);
        $deleted = 0;

        foreach ($webhookAddresses as $webhookAddress) {
            $this->setDeleted($webhookAddress);
            ++$deleted;
        }

        if ($deleted > 0) {
            $this->entityManager->flush();
        }

        return [$deleted, $count - $deleted];
    }

    /**
     * @param array $ids webhook event IDs to resend
     *
     * @return array [$deleted, $failed]
     *
     * @throws \Exception
     */
    public function handleResendMultiple(array $ids): array
    {
        $webhookEvents = $this->entityManager->getRepository(WebhookEvent::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        $count = count($webhookEvents);
        $sent = 0;
        foreach ($webhookEvents as $webhookEvent) {
            $this->requester->send($webhookEvent);
            ++$sent;
        }

        return [$sent, $count - $sent];
    }

    public function handleTestSendMultiple(array $ids): array
    {
        $webhookAddresses = $this->entityManager->getRepository(WebhookAddress::class)->findBy(
            [
                'id' => $ids,
                'deletedAt' => null,
            ]
        );

        $count = count($webhookAddresses);
        $sent = 0;

        foreach ($webhookAddresses as $webhookAddress) {
            $this->handleTestSend($webhookAddress);
            ++$sent;
        }

        return [$sent, $count - $sent];
    }

    private function setDeleted(WebhookAddress $webhookAddress): void
    {
        $webhookAddress->setDeletedAt(new \DateTime());
    }
}
