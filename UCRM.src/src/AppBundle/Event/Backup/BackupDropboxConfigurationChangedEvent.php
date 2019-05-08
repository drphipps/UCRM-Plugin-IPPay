<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Backup;

use Symfony\Component\EventDispatcher\Event;

/*
 * Dropbox configuration has been changed, we need to upload the backups using new configuration.
 */
class BackupDropboxConfigurationChangedEvent extends Event
{
}
