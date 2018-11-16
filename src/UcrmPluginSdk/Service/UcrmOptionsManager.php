<?php
/*
 * This file is part of UCRM Plugin SDK.
 *
 * Copyright (c) 2018 Ubiquiti Networks
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ubnt\UcrmPluginSdk\Service;

use Ubnt\UcrmPluginSdk\Data\UcrmOptions;

/**
 * This class can be used to retrieve automatically generated UCRM options from `ucrm.json` file.
 *
 * @see https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/docs/file-structure.md#ucrmjson
 */
class UcrmOptionsManager extends AbstractOptionsManager
{
    private const UCRM_JSON = 'ucrm.json';

    /**
     * @var UcrmOptions|null
     */
    private $options;

    /**
     * Returns (cached) instance of UcrmOptions data class,
     * which holds automatically generated UCRM options from `ucrm.json` file.
     *
     * @see https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/docs/file-structure.md#ucrmjson
     *
     * Example usage:
     *
     *     $ucrmOptionsManager = new UcrmOptionsManager();
     *     $options = $ucrmOptionsManager->loadOptions();
     *     echo $options->pluginPublicUrl;
     */
    public function loadOptions(): UcrmOptions
    {
        if (! $this->options) {
            $this->options = $this->getOptions();
        }

        return $this->options;
    }

    /**
     * Refreshes the cached instance of UcrmOptions held in this class.
     *
     * Example usage:
     *
     *     $ucrmOptionsManager = new UcrmOptionsManager();
     *     $options = $ucrmOptionsManager->loadOptions();
     *
     *     // ... long operation ...
     *     // ... long operation ...
     *     // ... long operation ...
     *
     *     $ucrmOptionsManager->updateOptions();
     *     // options are now up to date
     *     $options = $ucrmOptionsManager->loadOptions();
     */
    public function updateOptions(): void
    {
        $this->options = $this->getOptions();
    }

    private function getOptions(): UcrmOptions
    {
        $options = new UcrmOptions();
        $reflectionClass = new \ReflectionClass($options);

        $data = $this->getDataFromJson(self::UCRM_JSON);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (array_key_exists($reflectionProperty->getName(), $data)) {
                $reflectionProperty->setValue($options, $data[$reflectionProperty->getName()]);
            }
        }

        return $options;
    }
}