<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Csv\CsvBuilder;
use AppBundle\Component\FccReporting\Geocoder;
use AppBundle\Component\FccReporting\GeocoderAddress;
use AppBundle\Component\FccReporting\GeocoderException;
use AppBundle\Component\FccReporting\GeocoderOutageException;
use AppBundle\Component\HeaderNotification\HeaderNotifier;
use AppBundle\Entity\Client;
use AppBundle\Entity\Download;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\User;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Factory\Fcc\GeocoderAddressFactory;
use AppBundle\RabbitMq\Fcc\FccReportMessage;
use AppBundle\Service\DownloadFinisher;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings;
use Psr\Log\LoggerInterface;
use RabbitMqBundle\RabbitMqEnqueuer;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionDispatcher;

/**
 * @see https://transition.fcc.gov/form477/477inst.pdf
 */
class FccFacade
{
    /**
     * @var DownloadFinisher
     */
    private $downloadFinisher;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Geocoder
     */
    private $geocoder;

    /**
     * @var GeocoderAddressFactory
     */
    private $geocoderAddressFactory;

    /**
     * @var HeaderNotifier
     */
    private $headerNotifier;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        DownloadFinisher $downloadFinisher,
        EntityManager $entityManager,
        Geocoder $geocoder,
        GeocoderAddressFactory $geocoderAddressFactory,
        HeaderNotifier $headerNotifier,
        LoggerInterface $logger,
        Options $options,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        TransactionDispatcher $transactionDispatcher,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->downloadFinisher = $downloadFinisher;
        $this->entityManager = $entityManager;
        $this->geocoder = $geocoder;
        $this->geocoderAddressFactory = $geocoderAddressFactory;
        $this->headerNotifier = $headerNotifier;
        $this->logger = $logger;
        $this->options = $options;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->translator = $translator;
        $this->router = $router;
    }

    public function findAndUpdateBlock(Service $service): ?string
    {
        if (! $this->options->get(Option::FCC_ALWAYS_USE_GPS) && $service->getStreet1()) {
            try {
                $block = $this->geocoder->geocodeAddress(
                    $this->geocoderAddressFactory->create($service)
                );
                $this->updateServiceFccBlockId($service, $block);
            } catch (GeocoderException $exception) {
                // Ignore silently - address not found.
            }
        }

        if (
            ! $service->getFccBlockId()
            && $service->getAddressGpsLat()
            && $service->getAddressGpsLon()
        ) {
            try {
                $block = $this->geocoder->geocodeCoordinates(
                    $service->getAddressGpsLat(),
                    $service->getAddressGpsLon()
                );
                $this->updateServiceFccBlockId($service, $block);
            } catch (GeocoderException $exception) {
                // Ignore silently - address not found.
            }
        }

        return $service->getFccBlockId();
    }

    public function finishFixedBroadbandSubscriptionReport(int $downloadId, array $organizationIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'fixed_broadband_subscription.csv',
            function () use ($downloadId, $organizationIds) {
                try {
                    return $this->createFixedBroadbandSubscriptionReport($downloadId, $organizationIds);
                } catch (GeocoderOutageException $e) {
                    $this->logger->warning('FCC report could not be generated. Geocoding service has outage.');
                    throw $e;
                }
            }
        );
    }

    public function finishFixedBroadbandDeploymentReport(int $downloadId, array $organizationIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'fixed_broadband_deployment.csv',
            function () use ($downloadId, $organizationIds) {
                try {
                    return $this->createFixedBroadbandDeploymentReport($downloadId, $organizationIds);
                } catch (GeocoderOutageException $e) {
                    $this->logger->warning('FCC report could not be generated. Geocoding service has outage.');
                    throw $e;
                }
            }
        );
    }

    public function prepareDeploymentDownload(string $name, array $organizations, User $user): void
    {
        $this->prepareDownload($name, $organizations, $user, FccReportMessage::TYPE_FIXED_BROADBAND_DEPLOYMENT);
    }

    public function prepareSubscriptionDownload(string $name, array $organizations, User $user): void
    {
        $this->prepareDownload($name, $organizations, $user, FccReportMessage::TYPE_FIXED_BROADBAND_SUBSCRIPTION);
    }

    /**
     * @see https://transition.fcc.gov/form477/FBS/formatting_fbs.pdf
     *
     * @throws GeocoderOutageException
     */
    private function createFixedBroadbandSubscriptionReport(int $downloadId, array $organizationIds): string
    {
        $services = $this->entityManager->getRepository(Service::class)->getServicesForFccReport($organizationIds);
        $report = [];
        $failedServices = [
            Geocoder::BATCH_RETURN_NO_MATCH => [],
            Geocoder::BATCH_RETURN_TIE => [],
        ];
        foreach ($this->findBlocks($services) as $serviceId => $blockId) {
            $service = $services[$serviceId];
            if (in_array($blockId, Geocoder::BATCH_UNSUCCESSFUL, true)) {
                $failedServices[$blockId][] = $service;

                continue;
            }

            $tractId = Strings::substring($blockId, 0, 11);

            $tariff = $service->getTariff();

            $technologyOfTransmission = $tariff->getTechnologyOfTransmission()
                ?? Tariff::DEFAULT_TRANSMISSION_TECHNOLOGY;
            // Subscription report has limited list of technologies
            // see https://transition.fcc.gov/form477/FBS/formatting_fbs.pdf
            $technologyOfTransmission = $technologyOfTransmission - $technologyOfTransmission % 10;
            $key = sprintf(
                '%s/%s/%s/%s',
                $tractId,
                $technologyOfTransmission,
                round($tariff->getDownloadSpeed() ?? 0, 3),
                round($tariff->getUploadSpeed() ?? 0, 3)
            );
            $isConsumer = $service->getTariff()->getFccServiceType() === Tariff::FCC_SERVICE_TYPE_CONSUMER
                || (
                    $service->getTariff()->getFccServiceType() === null
                    && $service->getClient()->getClientType() === Client::TYPE_RESIDENTIAL
                );

            if (! array_key_exists($key, $report)) {
                $report[$key] = [
                    'connections' => 1,
                    'consumers' => (int) $isConsumer,
                ];
            } else {
                ++$report[$key]['connections'];
                $report[$key]['consumers'] += (int) $isConsumer;
            }
        }

        $rows = [];
        foreach ($report as $key => $counts) {
            $cols = explode('/', $key);
            $rows[] = [
                'tract' => $cols[0],
                'technology' => $cols[1],
                'downstream' => $cols[2],
                'upstream' => $cols[3],
                'connections' => $counts['connections'],
                'consumers' => $counts['consumers'],
            ];
        }

        $this->sendFailedFccServicesNotification($downloadId, $failedServices);

        return $this->getFixedBroadbandSubscriptionCsv($rows);
    }

    /**
     * @see https://transition.fcc.gov/form477/FBD/formatting_fbd.pdf
     *
     * @throws GeocoderOutageException
     */
    private function createFixedBroadbandDeploymentReport(int $downloadId, array $organizationIds): string
    {
        $services = $this->entityManager->getRepository(Service::class)->getServicesForFccReport($organizationIds);
        $report = [];
        $failedServices = [
            Geocoder::BATCH_RETURN_NO_MATCH => [],
            Geocoder::BATCH_RETURN_TIE => [],
        ];
        foreach ($this->findBlocks($services) as $serviceId => $blockId) {
            $service = $services[$serviceId];
            if (in_array($blockId, Geocoder::BATCH_UNSUCCESSFUL, true)) {
                $failedServices[$blockId][] = $service;

                continue;
            }

            $tariff = $service->getTariff();
            $organization = $service->getClient()->getOrganization();

            $key = sprintf(
                '%s/%s/%s',
                $blockId,
                $organization->getName(),
                $tariff->getTechnologyOfTransmission() ?? Tariff::DEFAULT_TRANSMISSION_TECHNOLOGY
            );
            $isConsumer = $service->getTariff()->getFccServiceType() === Tariff::FCC_SERVICE_TYPE_CONSUMER
                || (
                    $service->getTariff()->getFccServiceType() === null
                    && $service->getClient()->getClientType() === Client::TYPE_RESIDENTIAL
                );
            $download = round($tariff->getDownloadSpeed() ?? 0, 3);
            $upload = round($tariff->getUploadSpeed() ?? 0, 3);
            $maximumContractualDownstreamBandwidth = round(
                $service->getTariff()->getMaximumContractualDownstreamBandwidth() ?? 0,
                3
            );
            $maximumContractualUpstreamBandwidth = round(
                $service->getTariff()->getMaximumContractualUpstreamBandwidth() ?? 0,
                3
            );

            if (! array_key_exists($key, $report)) {
                $report[$key] = [
                    'consumer' => (int) $isConsumer,
                    'max_consumer_downstream' => $isConsumer ? $download : 0,
                    'max_consumer_upstream' => $isConsumer ? $upload : 0,
                    'business' => (int) ! $isConsumer,
                    'max_business_downstream' => ! $isConsumer ? $maximumContractualDownstreamBandwidth : 0,
                    'max_business_upstream' => ! $isConsumer ? $maximumContractualUpstreamBandwidth : 0,
                ];
            } else {
                if ($isConsumer) {
                    $report[$key]['consumer'] = 1;
                    $report[$key]['max_consumer_downstream'] = max($report[$key]['max_consumer_downstream'], $download);
                    $report[$key]['max_consumer_upstream'] = max($report[$key]['max_consumer_upstream'], $upload);
                } else {
                    $report[$key]['business'] = 1;
                    $report[$key]['max_business_downstream'] = max(
                        $report[$key]['max_business_downstream'],
                        $maximumContractualDownstreamBandwidth
                    );
                    $report[$key]['max_business_upstream'] = max(
                        $report[$key]['max_business_upstream'],
                        $maximumContractualUpstreamBandwidth
                    );
                }
            }
        }

        $rows = [];
        foreach ($report as $key => $data) {
            $cols = explode('/', $key);
            $rows[] = [
                'block' => $cols[0],
                'organization' => $cols[1],
                'technology' => $cols[2],
                'consumer' => $data['consumer'],
                'max_consumer_downstream' => $data['max_consumer_downstream'],
                'max_consumer_upstream' => $data['max_consumer_upstream'],
                'business' => $data['business'],
                'max_business_downstream' => $data['max_business_downstream'],
                'max_business_upstream' => $data['max_business_upstream'],
            ];
        }

        $this->sendFailedFccServicesNotification($downloadId, $failedServices);

        return $this->getFixedBroadbandDeploymentCsv($rows);
    }

    private function getFixedBroadbandSubscriptionCsv(array $rows): string
    {
        $builder = new CsvBuilder();

        $builder->setIncludeHeaderRow(false);

        array_map(
            function (array $row) use ($builder) {
                $builder->addData($row);
            },
            $rows
        );

        return $builder->getCsv();
    }

    private function getFixedBroadbandDeploymentCsv(array $rows): string
    {
        $builder = new CsvBuilder();

        $builder->setIncludeHeaderRow(false);

        array_map(
            function (array $row) use ($builder) {
                $builder->addData($row);
            },
            $rows
        );

        return $builder->getCsv();
    }

    /**
     * @return array|GeocoderAddress[]
     */
    private function getNewGeocoderAddresses(array $services): array
    {
        $addresses = [];
        array_map(
            function (Service $service) use (&$addresses) {
                if (! $service->getFccBlockId() && $service->getStreet1()) {
                    $addresses[$service->getId()] = $this->geocoderAddressFactory->create($service);
                }
            },
            $services
        );

        return $addresses;
    }

    private function prepareDownload(string $name, array $organizations, User $user, string $type): void
    {
        $download = new Download();

        $this->entityManager->transactional(
            function () use ($download, $name, $user) {
                $download->setName($name);
                $download->setCreated(new \DateTime());
                $download->setStatus(Download::STATUS_PENDING);
                $download->setUser($user);

                $this->entityManager->persist($download);
            }
        );

        $this->rabbitMqEnqueuer->enqueue(new FccReportMessage($download, $organizations, $type));
    }

    private function sendFailedFccServicesNotification(int $downloadId, array $failedServices): void
    {
        if (
            empty($failedServices[Geocoder::BATCH_RETURN_NO_MATCH])
            && empty($failedServices[Geocoder::BATCH_RETURN_TIE])
        ) {
            return;
        }

        $download = $this->entityManager->find(Download::class, $downloadId);
        $failedDownload = new Download();
        $failedDownload->setName(
            sprintf('%s - %s', $download->getName(), $this->translator->trans('failed services'))
        );
        $failedDownload->setCreated(new \DateTime());
        $failedDownload->setStatus(Download::STATUS_PENDING);
        $failedDownload->setUser($download->getUser());
        $this->entityManager->persist($failedDownload);
        $this->entityManager->flush($failedDownload);

        $this->downloadFinisher->finishDownload(
            $failedDownload->getId(),
            'fcc_failed_services.csv',
            function () use ($failedServices) {
                return $this->createFailedServicesReport($failedServices);
            },
            false
        );

        $notificationDescription = $this->translator->trans(
            'Census codes could not be obtained for some services in %reportName%.',
            [
                '%reportName%' => $download->getName(),
            ]
        );

        $notificationTitle = $this->translator->trans('FCC report is incomplete.');
        $notificationStatus = HeaderNotification::TYPE_WARNING;
        $notificationLink = $this->router->generate(
                'download_download',
                [
                    'id' => $failedDownload->getId(),
                ]
            );

        if ($user = $download->getUser()) {
            $this->headerNotifier->sendToAdmin(
                $user,
                $notificationStatus,
                $notificationTitle,
                $notificationDescription,
                $notificationLink
            );
        } else {
            $this->headerNotifier->sendToAllAdmins(
                $notificationStatus,
                $notificationTitle,
                $notificationDescription,
                $notificationLink
            );
        }
    }

    private function createFailedServicesReport(array $failedServices): string
    {
        $builder = new CsvBuilder();

        foreach ($failedServices[Geocoder::BATCH_RETURN_NO_MATCH] as $service) {
            $this->addFailedServicesData($builder, $service, Geocoder::BATCH_RETURN_NO_MATCH);
        }
        foreach ($failedServices[Geocoder::BATCH_RETURN_TIE] as $service) {
            $this->addFailedServicesData($builder, $service, Geocoder::BATCH_RETURN_TIE);
        }

        return $builder->getCsv();
    }

    private function addFailedServicesData(CsvBuilder $builder, Service $service, string $reason): void
    {
        $builder->addData(
            [
                'Client ID' => $service->getClient()->getId(),
                'Client' => $service->getClient()->getNameForView(),
                'Service ID' => $service->getId(),
                'Service' => $service->getName(),
                'Street' => $service->getStreet1(),
                'City' => $service->getCity(),
                'State' => $service->getState() ? $service->getState()->getCode() : '',
                'ZIP' => $service->getZipCode(),
                'Latitude' => $service->getAddressGpsLat(),
                'Longitude' => $service->getAddressGpsLon(),
                'Reason' => $reason,
            ]
        );
    }

    /**
     * @param Service[] $services
     *
     * @throws GeocoderOutageException
     */
    private function findBlocks(array $services): array
    {
        $return = [];
        foreach ($services as $service) {
            if (! $this->options->get(Option::FCC_ALWAYS_USE_GPS)) {
                $newGeocodedServices = $newGeocodedServices
                    ?? $this->geocoder->geocodeBatch($this->getNewGeocoderAddresses($services));

                if (
                    array_key_exists($service->getId(), $newGeocodedServices)
                ) {
                    if (! in_array($newGeocodedServices[$service->getId()], Geocoder::BATCH_UNSUCCESSFUL, true)) {
                        $this->updateServiceFccBlockId($service, $newGeocodedServices[$service->getId()]);
                    }

                    $return[$service->getId()] = $newGeocodedServices[$service->getId()];
                } else {
                    $return[$service->getId()] = $service->getFccBlockId() ?? Geocoder::BATCH_RETURN_NO_MATCH;
                }
            } else {
                if (
                    (! $block = $service->getFccBlockId())
                    && $service->getAddressGpsLat()
                    && $service->getAddressGpsLon()
                ) {
                    try {
                        $block = $this->geocoder->geocodeCoordinates(
                            $service->getAddressGpsLat(),
                            $service->getAddressGpsLon()
                        );
                        if ($block) {
                            $this->updateServiceFccBlockId($service, $block);
                        }
                    } catch (GeocoderException $exception) {
                        $return[$service->getId()] = Geocoder::BATCH_RETURN_NO_MATCH;

                        continue;
                    }
                }

                $return[$service->getId()] = $service->getFccBlockId() ?? Geocoder::BATCH_RETURN_NO_MATCH;
            }
        }

        return $return;
    }

    private function updateServiceFccBlockId(Service $service, string $fccBlockId)
    {
        $serviceBeforeUpdate = clone $service;
        $this->transactionDispatcher->transactional(
            function () use ($service, $serviceBeforeUpdate, $fccBlockId) {
                $service->setFccBlockId($fccBlockId);
                yield new ServiceEditEvent($service, $serviceBeforeUpdate);
            }
        );
    }
}
