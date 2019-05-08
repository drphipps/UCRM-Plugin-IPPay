<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Geocoder;

use AppBundle\Component\Geocoder\Google\GoogleGeocodingException;
use AppBundle\Entity\Client;
use AppBundle\Facade\ClientFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ClientGeocodeRequestConsumer extends AbstractConsumer
{
    /**
     * @var ClientFacade
     */
    private $clientFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        ClientFacade $clientFacade,
        LoggerInterface $logger,
        Options $options
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->clientFacade = $clientFacade;
    }

    protected function getMessageClass(): string
    {
        return ClientGeocodeRequestMessage::class;
    }

    public function executeBody(array $data): int
    {
        if (! $client = $this->getClient((int) $data['clientId'])) {
            return self::MSG_REJECT;
        }

        if (! $this->geocode($client)) {
            return self::MSG_REJECT;
        }

        return self::MSG_ACK;
    }

    private function getClient(int $clientId): ?Client
    {
        $client = $this->entityManager->find(Client::class, $clientId);
        if (! $client) {
            $this->logger->warning(
                sprintf(
                    'Client not found: ID %d',
                    $clientId
                )
            );

            return null;
        }
        // note: this consumer is only used by imports - if this condition is true, user must have changed data
        if ($client->hasAddressGps()) {
            $this->logger->warning(
                sprintf(
                    'Client already geocoded: ID %d',
                    $clientId
                )
            );

            return null;
        }

        return $client;
    }

    private function geocode(Client $client): bool
    {
        try {
            $this->clientFacade->geocode($client);

            return true;
        } catch (GoogleGeocodingException $googleGeocodingException) {
            $this->logger->warning(
                sprintf(
                    'Cannot geocode client %d (%s).',
                    $client->getId(),
                    $googleGeocodingException->getMessage()
                )
            );
        } catch (\RuntimeException $runtimeException) {
            $this->logger->warning(
                sprintf(
                    'Failed geocoding client %d (%s).',
                    $client->getId(),
                    $runtimeException->getMessage()
                )
            );
        }

        return false;
    }
}
