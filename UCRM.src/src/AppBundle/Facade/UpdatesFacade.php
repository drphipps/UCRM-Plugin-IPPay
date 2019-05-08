<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Exception\UpdateException;
use AppBundle\FileManager\UpdatesFileManager;

class UpdatesFacade
{
    /**
     * @var UpdatesFileManager
     */
    private $updatesFileManager;

    /**
     * @var string
     */
    private $installedVersion;

    public function __construct(UpdatesFileManager $updatesFileManager, string $installedVersion)
    {
        $this->updatesFileManager = $updatesFileManager;
        $this->installedVersion = $installedVersion;
    }

    public function requestUpdate(string $version, string $updateFileAccessKey): void
    {
        if (! version_compare($version, '0.0.1', '>=')) {
            throw new UpdateException('Requested version is not valid.');
        }

        if ($this->updatesFileManager->getRequestedUpdate()) {
            throw new UpdateException('An update is already requested.');
        }

        if (version_compare($version, $this->installedVersion, '<=')) {
            throw new UpdateException('Update is not possible, you can only update to newer version.');
        }

        $this->updatesFileManager->requestUpdate($version, $updateFileAccessKey);
    }
}
