<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Statistics;

use AppBundle\DataProvider\UcrmStatisticsDataProvider;
use AppBundle\Entity\General;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Service\Options;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class StatisticsSender
{
    private const INVALID_TOKEN_MESSAGE = 'Token is in invalid format.';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $token;

    /**
     * @var GuzzleClient
     */
    private $statisticsClient;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    /**
     * @var UcrmStatisticsDataProvider
     */
    private $statisticsDataProvider;

    public function __construct(
        string $version,
        GuzzleClient $statisticsClient,
        LoggerInterface $logger,
        Options $options,
        OptionsFacade $optionsFacade,
        UcrmStatisticsDataProvider $statisticsDataProvider
    ) {
        $this->statisticsClient = $statisticsClient;
        $this->logger = $logger;
        $this->options = $options;
        $this->version = $version;
        $this->optionsFacade = $optionsFacade;
        $this->statisticsDataProvider = $statisticsDataProvider;
    }

    public function randomWait(): void
    {
        $minimum = 0;
        $maximum = 3600;

        $seconds = mt_rand($minimum, $maximum);
        $this->logger->debug(sprintf('Delay sending statistics for %d seconds', $seconds));
        sleep($seconds);
    }

    public function send(): bool
    {
        if (null === $this->statisticsClient->getConfig('base_uri')) {
            $this->logger->error('UCRM statistics server URL not set.');

            return false;
        }

        $this->logger->info('Sending statistics.');

        $this->token = $this->options->getGeneral(General::CRM_API_TOKEN);
        if (null === $this->token && ! $this->refreshToken()) {
            $this->logger->error('Could not retrieve token.');

            return false;
        }

        $tryAgain = true;
        while (true) {
            try {
                $this->sendVersion();
                $this->sendStatistics();
                break;
            } catch (GuzzleException $e) {
                if ($tryAgain && Strings::contains($e->getMessage(), self::INVALID_TOKEN_MESSAGE)) {
                    $tryAgain = false;

                    if ($this->refreshToken()) {
                        continue;
                    }
                }

                $this->logger->error($e->getMessage());

                return false;
            }
        }

        return true;
    }

    public function markAsDiscardedWithBackupRestore(): void
    {
        if ($this->statisticsClient->getConfig('base_uri') === null) {
            $this->logger->error('UCRM statistics server URL not set.');

            return;
        }

        $this->token = $this->options->getGeneral(General::CRM_API_TOKEN);
        if ($this->token === null && ! $this->refreshToken()) {
            $this->logger->error('Could not retrieve token.');

            return;
        }

        $tryAgain = true;
        while (true) {
            try {
                $this->statisticsClient->patch(
                    'statistics',
                    [
                        'json' => [
                            'token' => $this->token,
                            'discardedWithBackupRestore' => true,
                        ],
                    ]
                );
                $this->logger->info('Marked token as discarded with backup restore.');

                break;
            } catch (GuzzleException $e) {
                if ($tryAgain && Strings::contains($e->getMessage(), self::INVALID_TOKEN_MESSAGE)) {
                    $tryAgain = false;

                    if ($this->refreshToken()) {
                        continue;
                    }
                }

                $this->logger->error($e->getMessage());

                return;
            }
        }
    }

    private function sendVersion(): void
    {
        $this->statisticsClient->put(
            'version',
            [
                'json' => [
                    'token' => $this->token,
                    'version' => $this->version,
                ],
            ]
        );

        $this->logger->info('Version sent.');
    }

    private function sendStatistics(): void
    {
        $this->statisticsClient->put(
            'statistics',
            [
                'json' => $this->statisticsDataProvider->getData($this->token)->toArray(),
            ]
        );

        $this->logger->info('Statistics sent.');
    }

    private function refreshToken(): bool
    {
        $response = $this->statisticsClient->post('token');
        if (! $this->isResponseSuccessful($response)) {
            return false;
        }

        $data = $this->getResponseData($response);
        $this->token = $data['token'];
        $this->optionsFacade->updateGeneral(General::CRM_API_TOKEN, $this->token);

        return true;
    }

    private function isResponseSuccessful(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }

    private function getResponseData(ResponseInterface $response): array
    {
        return Json::decode((string) $response->getBody(), Json::FORCE_ARRAY);
    }
}
