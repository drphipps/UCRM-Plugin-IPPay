<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class BackupAdditionalData implements SettingsDataInterface
{
    /**
     * @var int
     *
     * @Identifier(Option::BACKUP_LIFETIME_COUNT)
     *
     * @Assert\NotBlank()
     * @Assert\GreaterThan(0)
     */
    public $backupLifetimeCount;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_REMOTE_DROPBOX)
     */
    public $backupRemoteDropbox;

    /**
     * @var string|null
     *
     * @Identifier(Option::BACKUP_REMOTE_DROPBOX_TOKEN)
     * @Assert\Length(max=128)
     * @Assert\Expression(
     *     expression="not this.backupRemoteDropbox or value != ''",
     *     message="This field is required for Dropbox synchronization."
     * )
     */
    public $backupRemoteDropboxToken;

    /**
     * @var string
     *
     * @Identifier(Option::BACKUP_FILENAME_PREFIX)
     * @Assert\Regex(
     *     pattern="~^[\w-]*$~",
     *     message="This prefix is not valid. Valid characters are letters, digits, hyphens and underscores."
     * )
     */
    public $backupFilenamePrefix;
}
