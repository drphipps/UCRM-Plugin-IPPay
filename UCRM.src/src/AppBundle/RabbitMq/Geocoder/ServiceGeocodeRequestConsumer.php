<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Geocoder;

use AppBundle\Component\Geocoder\Google\GoogleGeocodingException;
use AppBundle\Entity\Service;
use AppBundle\Facade\ServiceFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ServiceGeocodeRequestConsumer extends AbstractConsumer
{
    /**
     * @var ServiceFacade
     */
    private $serviceFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        ServiceFacade $serviceFacade,
        LoggerInterface $logger,
        Options $options
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->serviceFacade = $serviceFacade;
    }

    protected function getMessageClass(): string
    {
        return ServiceGeocodeRequestMessage::class;
    }

    public function executeBody(array $data): int
    {
        if (! $service = $this->getService((int) $data['serviceId'])) {
            return self::MSG_REJECT;
        }

        if (! $this->geocode($service)) {
            return self::MSG_REJECT;
        }

        return self::MSG_ACK;
    }

    private function getService(int $serviceId): ?Service
    {
        $service = $this->entityManager->find(Service::class, $serviceId);
        if (! $service) {
            $this->logger->warning(
                sprintf(
                    'Service not found: ID %d',
                    $serviceId
                )
            );

            return null;
        }
        // note: this consumer is only used by imports - if this condition is true, user must have changed data
        if ($service->hasAddressGps()) {
            $this->logger->warning(
                sprintf(
                    'Service already geocoded: ID %d',
                    $serviceId
                )
            );

            return null;
        }

        return $service;
    }

    private function geocode(Service $service): bool
    {
        try {
            $this->serviceFacade->geocode($service);

            return true;
        } catch (GoogleGeocodingException $googleGeocodingException) {
            $this->logger->warning(
                sprintf(
                    'Cannot geocode service %d (%s).',
                    $service->getId(),
                    $googleGeocodingException->getMessage()
                )
            );
        } catch (\RuntimeException $runtimeException) {
            $this->logger->warning(
                sprintf(
                    'Failed geocoding service %d (%s).',
                    $service->getId(),
                    $runtimeException->getMessage()
                )
            );
        }

        return false;
    }
}
