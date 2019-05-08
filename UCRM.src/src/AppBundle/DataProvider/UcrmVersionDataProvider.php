<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\Command\Version\Checker;
use AppBundle\Entity\Option;
use AppBundle\Entity\UcrmVersion;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;

class UcrmVersionDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var string
     */
    private $installedVersion;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        string $installedVersion
    ) {
        $this->entityManager = $entityManager;
        $this->options = $options;
        $this->installedVersion = $installedVersion;
    }

    public function getCurrentVersion(): string
    {
        return $this->installedVersion;
    }

    public function getCurrentUpdateChannel(): string
    {
        return $this->options->get(Option::UPDATE_CHANNEL);
    }

    public function isUpdateAvailable(): bool
    {
        return $this->installedVersion !== $this->getLatestAvailableVersion($this->getCurrentUpdateChannel());
    }

    /**
     * @return string[] - [$channel => $version]
     */
    public function getLatestAvailableVersions(): array
    {
        return [
            Option::UPDATE_CHANNEL_STABLE => $this->getLatestAvailableVersion(Option::UPDATE_CHANNEL_STABLE),
            Option::UPDATE_CHANNEL_BETA => $this->getLatestAvailableVersion(Option::UPDATE_CHANNEL_BETA),
        ];
    }

    /**
     * Returns the latest available version for the channel.
     * Can be stable even if on beta, if stable is newer (e.g. 2.14.1 > 2.14.0-beta4).
     */
    private function getLatestAvailableVersion(string $channel): string
    {
        $latestVersions = $this->getLatestVersions();

        return (string) array_reduce(
            [
                $latestVersions[Option::UPDATE_CHANNEL_STABLE] ?? '0.0.0',
                $channel === Option::UPDATE_CHANNEL_BETA
                    ? $latestVersions[Option::UPDATE_CHANNEL_BETA] ?? '0.0.0'
                    : '0.0.0',
            ],
            function (string $version1, string $version2) {
                return version_compare($version1, $version2, '>')
                    ? $version1
                    : $version2;
            },
            $this->installedVersion
        );
    }

    /**
     * Load latest versions from database cache.
     *
     * @see Checker::check()
     *
     * @return string[] - [$channel => $version]
     */
    private function getLatestVersions(): array
    {
        $ucrmVersions = $this->entityManager->getRepository(UcrmVersion::class)->findAll();

        $latestVersions = [];
        foreach ($ucrmVersions as $ucrmVersion) {
            $latestVersions[$ucrmVersion->getChannel()] = $ucrmVersion->getVersion();
        }

        return $latestVersions;
    }
}
