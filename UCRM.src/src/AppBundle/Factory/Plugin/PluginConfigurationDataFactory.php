<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Plugin;

use AppBundle\Entity\Plugin;
use AppBundle\Form\Data\PluginConfigurationData;
use AppBundle\Service\Plugin\PluginManifest;
use AppBundle\Util\DateTimeFactory;
use Ds\Set;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class PluginConfigurationDataFactory
{
    public function create(
        Plugin $plugin,
        PluginManifest $manifest,
        array $config,
        ?Set $existingFiles = null
    ): PluginConfigurationData {
        $data = new PluginConfigurationData();
        $data->executionPeriod = $plugin->getExecutionPeriod();
        $data->configuration = $config;

        foreach ($manifest->configuration as $configuration) {
            if ($configuration->type === FileType::class) {
                $data->configuration[$configuration->key] = null;

                if ($existingFiles && $config[$configuration->key] ?? false) {
                    $existingFiles->add($configuration->key);
                }
            }

            if (
                ($config[$configuration->key] ?? false)
                && in_array($configuration->type, [DateTimeType::class, DateType::class], true)
            ) {
                $data->configuration[$configuration->key] = DateTimeFactory::createFromFormat(
                    $configuration->type === DateTimeType::class ? \DateTime::ATOM : 'Y-m-d',
                    $config[$configuration->key]
                );
            }
        }

        return $data;
    }
}
