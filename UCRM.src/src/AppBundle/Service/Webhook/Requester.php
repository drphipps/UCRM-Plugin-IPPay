<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Webhook;

use ApiBundle\Mapper\WebhookEventMapper;
use AppBundle\DataProvider\WebhookAddressDataProvider;
use AppBundle\Entity\WebhookAddress;
use AppBundle\Entity\WebhookEvent;
use AppBundle\Entity\WebhookEventRequest;
use AppBundle\Entity\WebhookEventType;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Nette\Utils\Json;

class Requester
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var WebhookEventMapper
     */
    private $mapper;

    /**
     * @var WebhookAddressDataProvider
     */
    private $webhookAddressDataProvider;

    public function __construct(
        Client $httpClient,
        EntityManagerInterface $entityManager,
        WebhookEventMapper $webhookEventMapper,
        WebhookAddressDataProvider $webhookAddressDataProvider
    ) {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
        $this->mapper = $webhookEventMapper;
        $this->webhookAddressDataProvider = $webhookAddressDataProvider;
    }

    public function send(WebhookEvent $webhookEvent): void
    {
        foreach ($this->webhookAddressDataProvider->getAllActive() as $webhookAddress) {
            if ($this->isTestEventSupportedByWebhook($webhookAddress, $webhookEvent)
                || $this->isLiveEventSupportedByWebhook($webhookAddress, $webhookEvent)) {
                $this->sendOne($webhookEvent, $webhookAddress);
            }
        }
    }

    public function sendOne(WebhookEvent $webhookEvent, WebhookAddress $address): void
    {
        $webhookEventRequest = new WebhookEventRequest();
        $webhookEventRequest->setWebHookEvent($webhookEvent);
        $webhookEventRequest->setWebhookAddress($address);
        $webhookEventRequest->setVerifySslCertificate($address->isVerifySslCertificate());
        $options = $this->getConnectionOptions($address->isVerifySslCertificate());
        $options['json'] = $this->mapper->reflect($webhookEvent);
        $webhookEventRequest->setRequestBody(Json::encode($options['json']));
        $this->entityManager->persist($webhookEventRequest);

        $startTime = microtime(true);
        $responseCode = 598;
        $responseText = 'Unable to connect';
        $exceptionMessage = null;
        try {
            $response = $this->httpClient->post($address->getUrl(), $options);
        } catch (\GuzzleHttp\Exception\RequestException $requestException) {
            $response = $requestException->getResponse();
            $exceptionMessage = $requestException->getMessage();
        }
        $endTime = microtime(true);
        if ($response) {
            $responseCode = $response->getStatusCode();
            $responseText = $response->getReasonPhrase();
            $webhookEventRequest->setResponseBody($response->getBody()->getContents());
        } elseif ($exceptionMessage) {
            $responseText .= ': ' . $exceptionMessage;
        }
        $webhookEventRequest->setResponseCode($responseCode);
        $webhookEventRequest->setReasonPhrase($responseText);
        $webhookEventRequest->setDuration((int) floor(($endTime - $startTime) * 1000));
        $this->entityManager->flush();
    }

    private function isLiveEventSupportedByWebhook(WebhookAddress $webhookAddress, WebhookEvent $webhookEvent): bool
    {
        return $webhookEvent->getChangeType() !== WebhookEvent::TEST
            && (
                $webhookAddress->isAnyEvent()
                || array_filter(
                    $webhookAddress->getWebhookEventTypes()->toArray(),
                    function (WebhookEventType $eventType) use ($webhookEvent) {
                        return $eventType->getEventName() === $webhookEvent->getEventName();
                    }
                )
        );
    }

    private function isTestEventSupportedByWebhook(WebhookAddress $webhookAddress, WebhookEvent $webhookEvent): bool
    {
        return $webhookEvent->getChangeType() === WebhookEvent::TEST
            && (
                ! $webhookEvent->getEntityId()
                || $webhookAddress->getId() === $webhookEvent->getEntityId()
            );
    }

    /**
     * @see \GuzzleHttp\RequestOptions
     */
    private function getConnectionOptions(bool $verifySsl): array
    {
        return [
            'connect_timeout' => 10.0,
            'read_timeout' => 10.0,
            'allow_redirects' => true,
            'timeout' => 10.0,
            'synchronous' => true,
            'verify' => $verifySsl,
        ];
    }
}
