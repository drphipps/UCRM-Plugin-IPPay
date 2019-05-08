<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;

final class TicketingData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::TICKETING_ENABLED)
     */
    public $ticketingEnabled;

    /**
     * @var int
     *
     * @Identifier(Option::TICKETING_IMAP_ATTACHMENT_FILESIZE_IMPORT_LIMIT)
     */
    public $attachmentImportLimit;
}
