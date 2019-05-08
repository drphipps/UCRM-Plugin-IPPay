<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use Symfony\Component\Filesystem\Filesystem;

class MaintenanceFileManager
{
    /**
     * @var string
     */
    private $maintenancePath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(string $maintenancePath)
    {
        $this->maintenancePath = $maintenancePath;
        $this->filesystem = new Filesystem();
    }

    public function enterMaintenanceMode(): void
    {
        $this->filesystem->touch($this->maintenancePath);
    }

    public function exitMaintenanceMode(): void
    {
        $this->filesystem->remove($this->maintenancePath);
    }
}
