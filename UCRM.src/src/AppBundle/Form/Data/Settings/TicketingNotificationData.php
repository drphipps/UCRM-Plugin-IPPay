<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;

final class TicketingNotificationData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::TICKETING_IMAP_AUTOMATIC_REPLY_ENABLED)
     */
    public $automaticReplyEnabled;

    /**
     * @var NotificationTemplate
     */
    public $automaticReplyNotificationTemplate;
}
