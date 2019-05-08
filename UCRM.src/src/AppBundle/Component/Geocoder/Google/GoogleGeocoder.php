<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Geocoder\Google;

use AppBundle\Component\Geocoder\GeocoderInterface;
use AppBundle\Component\Geocoder\LocationData;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use GuzzleHttp\Client;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Simple\ApcuCache;

class GoogleGeocoder implements GeocoderInterface
{
    private const ERROR_OVER_DAILY_LIMIT = 'OVER_DAILY_LIMIT';
    private const ERROR_OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT';
    private const ERROR_REQUEST_DENIED = 'REQUEST_DENIED';
    private const ERROR_UNKNOWN_ERROR = 'UNKNOWN_ERROR';
    private const FATAL_ERRORS = [
        self::ERROR_OVER_DAILY_LIMIT,
        self::ERROR_OVER_QUERY_LIMIT,
        self::ERROR_REQUEST_DENIED,
        self::ERROR_UNKNOWN_ERROR,
    ];

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $requestedAddress;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var ApcuCache
     */
    private $cache;

    public function __construct(Client $httpClient, LoggerInterface $logger, Options $options)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->options = $options;

        $this->cache = new ApcuCache(hash('md5', self::class), 3600);
    }

    /**
     * @throws GoogleGeocodingException
     */
    public function query(string $request): ?LocationData
    {
        $this->requestedAddress = $request;

        $addressHash = hash('md5', $request);
        if ($cachedLocation = $this->cache->get($addressHash)) {
            $this->logger->debug(sprintf('Location cached for: %s', $cachedLocation->address));

            return $cachedLocation;
        }

        $response = $this->fetch();
        if (! $response) {
            return null;
        }

        $this->handleErrors($response);

        if ($locationData = $this->parseResponse($response)) {
            $this->cache->set($addressHash, $locationData);
        }

        return $locationData;
    }

    private function fetch(): ?array
    {
        $query = http_build_query(
            [
                'address' => $this->requestedAddress,
                'key' => $this->options->get(Option::GOOGLE_API_KEY),
            ]
        );

        try {
            $this->logger->debug('Requesting ' . $query);
            $response = $this->httpClient->get(sprintf('?%s', $query));
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());

            return null;
        }
        if ($response->getStatusCode() !== 200 || ! $body = $response->getBody()) {
            $this->logger->warning('Bad response: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());

            return null;
        }

        try {
            return Json::decode($body, Json::FORCE_ARRAY);
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());

            return null;
        }
    }

    private function handleErrors(array $response): void
    {
        if (! array_key_exists('status', $response)) {
            throw new GoogleGeocodingException('Wrong response field \'status\' is missing.');
        }

        if (in_array($response['status'], self::FATAL_ERRORS, true)) {
            throw new GoogleGeocodingException(
                sprintf(
                    'Google Geocoding error occured: \'%s\' %s',
                    $response['status'],
                    $response['error_message'] ?? ''
                )
            );
        }
    }

    private function parseResponse(array $response): ?LocationData
    {
        if ($response['results'][0] ?? false) {
            $locationData = new LocationData();
            $locationData->lat = $response['results'][0]['geometry']['location']['lat'];
            $locationData->lon = $response['results'][0]['geometry']['location']['lng'];
        }

        return $locationData ?? null;
    }
}
