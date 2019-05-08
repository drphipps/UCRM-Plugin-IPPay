<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Geocoder\Nominatim;

use AppBundle\Component\Geocoder\GeocoderInterface;
use AppBundle\Component\Geocoder\LocationData;
use AppBundle\DataProvider\UserDataProvider;
use GuzzleHttp\Client;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Simple\ApcuCache;

class NominatimGeocoder implements GeocoderInterface
{
    private const SIMPLE_CACHE_TIMEOUT = 3600;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * @var string
     */
    private $requestedAddress;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UserDataProvider
     */
    private $userDataProvider;

    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(
        Client $httpClient,
        LoggerInterface $logger,
        UserDataProvider $userDataProvider
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->userDataProvider = $userDataProvider;
        $this->cache = new ApcuCache(hash('md5', self::class), self::SIMPLE_CACHE_TIMEOUT);
    }

    /**
     * @throws \RuntimeException
     */
    public function query(string $request): ?LocationData
    {
        $this->setUserAgent();
        $this->setRequestedAddress($request);
        $addressHash = hash('md5', $this->getRequestedAddress());
        if ($location = $this->cache->get($addressHash)) {
            $this->logger->debug('Location cached for: ' . $location->address);

            return $location;
        }

        $result = $this->fetch();
        if (! $result) {
            return null;
        }

        $location = new LocationData();
        $location->address = $this->requestedAddress;
        $location->lat = (float) $result['lat'];
        $location->lon = (float) $result['lon'];
        $this->cache->set($addressHash, $location);

        return $location;
    }

    private function fetch(): ?array
    {
        if (! $this->requestedAddress) {
            $this->logger->error('No request specified!');

            return null;
        }
        $url = sprintf('?q=%s&format=json', urlencode($this->requestedAddress));

        try {
            $this->logger->debug('Requesting ' . $url);
            $response = $this->httpClient->get($url, $this->getConnectionOptions());
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());

            return null;
        }
        if ($response->getStatusCode() !== 200 || ! $body = $response->getBody()) {
            $this->logger->warning('Bad response: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());

            return null;
        }

        try {
            $json = Json::decode($body, Json::FORCE_ARRAY);

            return $json[0] ?? null;
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());

            return null;
        }
    }

    private function getRequestedAddress(): ?string
    {
        return $this->requestedAddress;
    }

    private function setRequestedAddress($request): void
    {
        $this->requestedAddress = $request;
    }

    private function getConnectionOptions(): array
    {
        return [
            'synchronous' => true,
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => $this->userAgent,
            ],
        ];
    }

    /**
     * @throws \RuntimeException
     */
    private function setUserAgent(): void
    {
        $user = $this->userDataProvider->getSuperAdmin();
        if (! $user) {
            throw new \RuntimeException('Geocoder not available unless authenticated');
        }

        $this->userAgent = sprintf('UBNT UCRM %s', $user->getEmail() ?? '');
    }
}
