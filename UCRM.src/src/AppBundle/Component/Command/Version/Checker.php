<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Version;

use AppBundle\Entity\Option;
use AppBundle\Entity\UcrmVersion;
use AppBundle\Event\Version\VersionCheckerNotificationEvent;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class Checker
{
    /**
     * @var string
     */
    private $installedVersion;

    /**
     * @var Client
     */
    private $versionClient;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        string $installedVersion,
        Client $versionClient,
        TransactionDispatcher $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->installedVersion = $installedVersion;
        $this->versionClient = $versionClient;
        $this->transactionDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function check(): void
    {
        $this->logger->info('Checking available UCRM versions.');

        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) {
                foreach (Option::UPDATE_CHANNELS as $channel) {
                    try {
                        yield from $this->updateAvailableVersion($entityManager, $channel);
                    } catch (GuzzleException $e) {
                        $this->logger->error($e->getMessage());

                        continue;
                    }
                }
            }
        );
    }

    private function updateAvailableVersion(EntityManagerInterface $entityManager, string $channel): Generator
    {
        $availableVersion = trim((string) $this->versionClient->get($channel)->getBody());
        $ucrmVersion = $entityManager->getRepository(UcrmVersion::class)->findOneBy(
            [
                'channel' => $channel,
            ]
        );

        if (! $ucrmVersion) {
            $ucrmVersion = new UcrmVersion();
            $ucrmVersion->setChannel($channel);
        }

        if (
            $availableVersion !== $ucrmVersion->getVersion()
            && version_compare($availableVersion, $this->installedVersion, '>')
        ) {
            yield new VersionCheckerNotificationEvent($channel, $availableVersion);
        }

        $ucrmVersion->setVersion($availableVersion);
        $entityManager->persist($ucrmVersion);

        $this->logger->info(sprintf('Latest version on "%s" channel is "%s".', $channel, $availableVersion));
    }
}
