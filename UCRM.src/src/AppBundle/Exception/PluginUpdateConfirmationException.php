<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Exception;

use AppBundle\Service\Plugin\PluginManifest;

class PluginUpdateConfirmationException extends PluginException
{
    /**
     * @var string
     */
    private $tmpZipArchiveFileName;

    /**
     * @var PluginManifest|null
     */
    private $oldManifest;

    /**
     * @var PluginManifest
     */
    private $newManifest;

    public function __construct(string $tmpZipArchiveFileName, ?PluginManifest $oldManifest, PluginManifest $newManifest)
    {
        parent::__construct();

        $this->tmpZipArchiveFileName = $tmpZipArchiveFileName;
        $this->oldManifest = $oldManifest;
        $this->newManifest = $newManifest;
    }

    public function getTmpZipArchiveFileName(): string
    {
        return $this->tmpZipArchiveFileName;
    }

    public function getOldManifest(): ?PluginManifest
    {
        return $this->oldManifest;
    }

    public function getNewManifest(): PluginManifest
    {
        return $this->newManifest;
    }
}
