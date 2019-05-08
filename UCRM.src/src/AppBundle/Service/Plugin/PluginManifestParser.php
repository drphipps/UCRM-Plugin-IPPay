<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Plugin;

use AppBundle\Exception\PluginManifestException;
use Ds\Map;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

class PluginManifestParser
{
    private const SUPPORTED_MANIFEST_VERSIONS = [
        '1',
    ];

    private const REQUIRED_FIELDS = [
        'version',
        'information',
    ];

    private const INFORMATION_REQUIRED_FIELDS = [
        'name',
        'displayName',
        'description',
        'url',
        'version',
        'ucrmVersionCompliancy',
        'author',
    ];

    /**
     * @var string
     */
    private $installedVersion;

    public function __construct(string $installedVersion)
    {
        $this->installedVersion = $installedVersion;
    }

    /**
     * @throws PluginManifestException
     */
    public function getVerified(string $manifestJson): PluginManifest
    {
        $manifestArray = $this->parse($manifestJson);

        $this->verifyRequiredFields($manifestArray);

        $manifest = new PluginManifest();
        $manifest->version = $this->loadManifestVersion($manifestArray);
        $manifest->information = $this->loadPluginInformation($manifestArray);
        $manifest->isUcrmVersionCompliant = $this->isUcrmVersionCompliant($manifest->information);
        $manifest->configuration = $this->loadPluginOptions($manifestArray);
        $manifest->menu = $this->loadPluginMenu($manifestArray);

        return $manifest;
    }

    /**
     * @throws PluginManifestException
     */
    private function parse(string $json): array
    {
        try {
            $manifest = Json::decode($json, Json::FORCE_ARRAY);
        } catch (JsonException $exception) {
            throw new PluginManifestException('Plugin manifest is not valid JSON.');
        }

        return $manifest;
    }

    /**
     * @param mixed[] $manifestArray
     */
    private function verifyRequiredFields(array $manifestArray): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (! array_key_exists($field, $manifestArray)) {
                throw new PluginManifestException('Plugin manifest is missing required fields.');
            }
        }
    }

    /**
     * @param mixed[] $manifestArray
     */
    private function loadManifestVersion(array $manifestArray): string
    {
        if (! in_array($manifestArray['version'], self::SUPPORTED_MANIFEST_VERSIONS, true)) {
            throw new PluginManifestException('Plugin manifest version is not supported.');
        }

        return $manifestArray['version'];
    }

    /**
     * @param mixed[] $manifestArray
     */
    private function loadPluginInformation(array $manifestArray): PluginManifestInformation
    {
        $information = new PluginManifestInformation();

        foreach (self::INFORMATION_REQUIRED_FIELDS as $field) {
            if (! array_key_exists($field, $manifestArray['information'])) {
                throw new PluginManifestException('Plugin manifest is missing required fields.');
            }

            if ($field === 'ucrmVersionCompliancy') {
                $ucrmVersionCompliancy = $manifestArray['information']['ucrmVersionCompliancy'];

                if (! array_key_exists('min', $ucrmVersionCompliancy) || ! $ucrmVersionCompliancy['min']) {
                    throw new PluginManifestException('Plugin manifest is missing required fields.');
                }

                $information->ucrmVersionCompliancyMin = $ucrmVersionCompliancy['min'];
                $information->ucrmVersionCompliancyMax = $ucrmVersionCompliancy['max'] ?? null;
            } else {
                $information->{$field} = $manifestArray['information'][$field];
            }
        }

        if (! Strings::match($information->name, '~^[a-z0-9_-]+$~')) {
            throw new PluginManifestException('Plugin name contains invalid characters.');
        }

        return $information;
    }

    private function isUcrmVersionCompliant(PluginManifestInformation $information): bool
    {
        $isCompliant = version_compare(
            $this->installedVersion,
            $information->ucrmVersionCompliancyMin,
            '>='
        );

        if (null !== $information->ucrmVersionCompliancyMax) {
            $isCompliant = $isCompliant
                && version_compare(
                    $this->installedVersion,
                    $information->ucrmVersionCompliancyMax,
                    '<='
                );
        }

        return $isCompliant;
    }

    /**
     * @param mixed[] $manifestArray
     *
     * @return PluginManifestConfiguration[]
     */
    private function loadPluginOptions(array $manifestArray): array
    {
        $options = new Map();

        foreach ($manifestArray['configuration'] ?? [] as $configurationArray) {
            $config = $this->loadPluginConfiguration($configurationArray);
            if ($options->hasKey($config->key)) {
                throw new PluginManifestException('Plugin manifest contains duplicate entry in configuration section.');
            }
            $options->put($config->key, $config);
        }

        return $options->toArray();
    }

    /**
     * @param mixed[] $configurationArray
     */
    private function loadPluginConfiguration(array $configurationArray): PluginManifestConfiguration
    {
        if (! array_key_exists('key', $configurationArray) || ! array_key_exists('label', $configurationArray)) {
            throw new PluginManifestException('Plugin manifest is missing required fields.');
        }

        $configuration = new PluginManifestConfiguration();
        $configuration->key = $configurationArray['key'];
        $configuration->label = $configurationArray['label'];
        $configuration->description = $configurationArray['description'] ?? null;
        $configuration->required = (bool) ($configurationArray['required'] ?? true);

        if (
            array_key_exists('type', $configurationArray)
            && array_key_exists($configurationArray['type'], PluginManifestConfiguration::TYPE_NAMES)
        ) {
            $configuration->type = PluginManifestConfiguration::TYPE_NAMES[$configurationArray['type']];
        }

        $configuration->choices = $configurationArray['choices'] ?? [];

        return $configuration;
    }

    /**
     * @param mixed[] $manifestArray
     *
     * @return PluginMenuItem[]
     */
    private function loadPluginMenu(array $manifestArray): array
    {
        $options = [];

        foreach ($manifestArray['menu'] ?? [] as $menuItemArray) {
            $options[] = $this->loadPluginMenuItem($menuItemArray);
        }

        return $options;
    }

    /**
     * @param mixed[] $menuItemArray
     */
    private function loadPluginMenuItem(array $menuItemArray): PluginMenuItem
    {
        if (! array_key_exists('type', $menuItemArray) || ! array_key_exists('target', $menuItemArray)) {
            throw new PluginManifestException('Plugin manifest is missing required fields.');
        }

        $menuItem = new PluginMenuItem();
        $menuItem->key = $menuItemArray['key'] ?? null;
        $menuItem->label = $menuItemArray['label'] ?? null;
        $menuItem->type = $menuItemArray['type'];
        $menuItem->target = $menuItemArray['target'];
        $menuItem->parameters = $this->castItemsToStrings($menuItemArray['parameters'] ?? []);

        return $menuItem;
    }

    private function castItemsToStrings(array $param): array
    {
        return array_map(
            function ($value) {
                return is_array($value) ? $this->castItemsToStrings($value) : (string) $value;
            },
            $param
        );
    }
}
