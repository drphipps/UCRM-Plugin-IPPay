<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

abstract class AbstractPluginFileManager
{
    // These file names are used in plugins.sh as well, don't forget to update both locations when changing.
    public const FILE_PUBLIC = 'public.php';
    public const DIR_PUBLIC = 'public';

    protected const DIR_DATA = 'data';
    protected const DIR_DATA_FILES = 'files';
    protected const FILE_CONFIG = 'data/config.json';
    protected const FILE_LOG = 'data/plugin.log';
    protected const FILE_MAIN = 'main.php';
    protected const FILE_MANIFEST = 'manifest.json';

    public const FILE_INTERNAL_RUNNING_LOCK = '.ucrm-plugin-running';
    public const FILE_INTERNAL_EXECUTION_REQUESTED = '.ucrm-plugin-execution-requested';
    protected const FILE_INTERNAL_UCRM_CONFIG = 'ucrm.json';

    // These files are reserved by UCRM and cannot be contained in the plugin archive.
    protected const RESERVED_FILES = [
        self::FILE_INTERNAL_RUNNING_LOCK,
        self::FILE_INTERNAL_EXECUTION_REQUESTED,
        self::FILE_INTERNAL_UCRM_CONFIG,
    ];
}
