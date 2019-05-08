<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\FccReporting;

use AppBundle\Component\Csv\CsvBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use Symfony\Component\Cache\Adapter\ApcuAdapter;

class Geocoder
{
    private const SEARCH_TYPE_ADDRESS = 'address';
    private const SEARCH_TYPE_COORDINATES = 'coordinates';
    private const SEARCH_TYPE_ADDRESS_BATCH = 'addressbatch';

    private const BATCH_LIMIT = 50;
    private const BATCH_COORDINATES_TRY_LIMIT = 3;
    public const BATCH_UNSUCCESSFUL = [
        self::BATCH_RETURN_NO_MATCH,
        self::BATCH_RETURN_TIE,
    ];

    public const BATCH_RETURN_NO_MATCH = 'No_Match';
    public const BATCH_RETURN_TIE = 'Tie';

    private const BATCH_CSV_COLUMN_ID = 0;
    private const BATCH_CSV_COLUMN_STATUS = 2;
    private const BATCH_CSV_COLUMN_STATE = 8;
    private const BATCH_CSV_COLUMN_COUNTY = 9;
    private const BATCH_CSV_COLUMN_TRACT = 10;
    private const BATCH_CSV_COLUMN_BLOCK = 11;

    /**
     * @var Client
     */
    private $geocoderClient;

    /**
     * @var ApcuAdapter
     */
    private $cache;

    public function __construct(Client $geocoderClient)
    {
        $this->geocoderClient = $geocoderClient;
        $this->cache = new ApcuAdapter('FccReporting_Geocoder', 3600);
    }

    /**
     * @throws GeocoderException
     */
    public function geocodeAddress(GeocoderAddress $address): string
    {
        return $this->getCensusBlockCode(
            $this->doRequest(
                self::SEARCH_TYPE_ADDRESS,
                [
                    'street' => $address->street,
                    'city' => $address->city,
                    'state' => $address->state,
                    'zip' => $address->zip,
                ]
            )
        );
    }

    /**
     * @throws GeocoderException
     */
    public function geocodeCoordinates(float $latitude, float $longitude): string
    {
        $response = $this->doRequest(
            self::SEARCH_TYPE_COORDINATES,
            [
                'x' => $longitude,
                'y' => $latitude,
            ]
        );

        return $this->getCensusBlockCode($response);
    }

    /**
     * @throws GeocoderOutageException
     */
    public function geocodeBatch(array $addresses): array
    {
        $csvBatches = $this->getCsvBatches($addresses);
        $geocoded = [];

        foreach ($csvBatches as $csvBatch) {
            $cacheKey = md5(serialize($csvBatch));
            $cachedBatch = $this->cache->getItem($cacheKey);
            if ($cachedBatch->isHit()) {
                $processedBatch = $cachedBatch->get();
            } else {
                $processedBatch = $this->processCsvBatch($csvBatch, $addresses);

                // Only cache, when everything successful.
                if (
                    ! in_array(self::BATCH_RETURN_NO_MATCH, $processedBatch, true)
                    && ! in_array(self::BATCH_RETURN_TIE, $processedBatch, true)
                ) {
                    $cachedBatch->set($processedBatch);
                    $this->cache->saveDeferred($cachedBatch);
                }
            }

            // MUST be added, not merged, the keys are important (service IDs)
            $geocoded = $geocoded + $processedBatch;
        }
        $this->cache->commit();

        return $geocoded;
    }

    private function getCsvBatches(array $addresses): array
    {
        $builder = new CsvBuilder();

        $builder->setIncludeHeaderRow(false);

        $csvBatches = [];
        while (! empty($addresses)) {
            $batch = array_splice($addresses, 0, self::BATCH_LIMIT);
            array_map(
                function (GeocoderAddress $address) use ($builder) {
                    $builder->addData($address->toGeocoderArray());
                },
                $batch
            );

            $csvBatches[] = $builder->getCsv();
            $builder->resetData();
        }

        return $csvBatches;
    }

    /**
     * @throws GeocoderOutageException
     */
    private function processCsvBatch(string $csv, array $addresses): array
    {
        $responseCsv = $this->doBatchRequest($csv);

        $fp = fopen('php://temp', 'w+');
        assert($fp);
        fwrite($fp, $responseCsv);
        rewind($fp);

        $censusCodes = [];
        while (false !== ($data = fgetcsv($fp))) {
            $address = $addresses[$data[self::BATCH_CSV_COLUMN_ID]];
            $hasGps = null !== $address->gpsLat && null !== $address->gpsLon;
            $censusCodes[$data[self::BATCH_CSV_COLUMN_ID]] = null;

            if (! in_array($data[self::BATCH_CSV_COLUMN_STATUS], self::BATCH_UNSUCCESSFUL, true)) {
                $censusCodes[$data[self::BATCH_CSV_COLUMN_ID]] =
                    $data[self::BATCH_CSV_COLUMN_STATE]
                    . $data[self::BATCH_CSV_COLUMN_COUNTY]
                    . $data[self::BATCH_CSV_COLUMN_TRACT]
                    . $data[self::BATCH_CSV_COLUMN_BLOCK];
            } elseif ($hasGps) {
                $limit = self::BATCH_COORDINATES_TRY_LIMIT;
                while ($limit > 0 && $censusCodes[$data[self::BATCH_CSV_COLUMN_ID]] === null) {
                    // useless to geocode 0,0 GPS
                    if (! $address->gpsLat || ! $address->gpsLon) {
                        break;
                    }

                    try {
                        $censusCodes[$data[self::BATCH_CSV_COLUMN_ID]] = $this->geocodeCoordinates(
                            $address->gpsLat,
                            $address->gpsLon
                        );
                    } catch (GeocoderException $exception) {
                        $censusCodes[$data[self::BATCH_CSV_COLUMN_ID]] = null;
                    }

                    --$limit;
                }
            }

            if (null === $censusCodes[$data[self::BATCH_CSV_COLUMN_ID]]) {
                $censusCodes[$data[self::BATCH_CSV_COLUMN_ID]] = $data[self::BATCH_CSV_COLUMN_STATUS];
            }
        }

        return $censusCodes;
    }

    /**
     * @throws GeocoderOutageException
     */
    private function doBatchRequest(string $csv): string
    {
        $body = (string) $this->geocoderClient
            ->request(
                'POST',
                self::SEARCH_TYPE_ADDRESS_BATCH,
                [
                    'multipart' => [
                        [
                            'name' => 'benchmark',
                            'contents' => 'Public_AR_Current',
                        ],
                        [
                            'name' => 'vintage',
                            'contents' => 'Current_Current',
                        ],
                        [
                            'name' => 'addressFile',
                            'contents' => $csv,
                            'filename' => 'addresses.csv',
                        ],
                    ],
                ]
            )
            ->getBody();

        $this->checkOutage($body);

        return $body;
    }

    /**
     * @throws GeocoderException
     */
    private function doRequest(string $type, array $data): array
    {
        $options = [
            'benchmark' => 'Public_AR_Current',
            'vintage' => 'Current_Current',
            'format' => 'json',
        ];

        try {
            $response = $this->geocoderClient
                ->get(
                    sprintf(
                        '%s?%s',
                        $type,
                        http_build_query(array_merge($options, $data))
                    )
                )
                ->getBody();
            $response = Json::decode((string) $response, Json::FORCE_ARRAY);
        } catch (JsonException $exception) {
            throw new GeocoderException('Invalid response.', $exception->getCode(), $exception);
        } catch (ClientException $exception) {
            throw new GeocoderException('Bad request.', $exception->getCode(), $exception);
        } catch (ConnectException $exception) {
            throw new GeocoderException('Failed to connect.', $exception->getCode(), $exception);
        }
        if (! array_key_exists('result', $response)) {
            throw new GeocoderException('Invalid response.');
        }

        return $response['result'];
    }

    /**
     * @throws GeocoderException
     */
    private function getCensusBlockCode(array $response): string
    {
        $geographies = $this->getGeographies($response);
        if (! array_key_exists('2010 Census Blocks', $geographies)) {
            throw new GeocoderException('Invalid response.');
        }

        $block = reset($geographies['2010 Census Blocks']);
        if (! $block) {
            throw new GeocoderException('Not found.');
        }

        if (! array_key_exists('GEOID', $block)) {
            throw new GeocoderException('Invalid response.');
        }

        return $block['GEOID'];
    }

    /**
     * @throws GeocoderException
     */
    private function getGeographies(array $response): array
    {
        if (array_key_exists('addressMatches', $response)) {
            $address = reset($response['addressMatches']);

            if (! is_array($address) || ! array_key_exists('geographies', $address)) {
                throw new GeocoderException('Invalid response.');
            }

            return $address['geographies'];
        }

        if (array_key_exists('geographies', $response)) {
            return $response['geographies'];
        }

        throw new GeocoderException('Invalid response.');
    }

    /**
     * @throws GeocoderOutageException
     */
    private function checkOutage(string $body): void
    {
        if (
            Strings::contains($body, 'The system you have attempted to reach is unavailable.')
            || Strings::contains($body, '<title>Website is unavailable</title>')
        ) {
            throw new GeocoderOutageException('Geocoding service is currently unavailable.');
        }
    }
}
