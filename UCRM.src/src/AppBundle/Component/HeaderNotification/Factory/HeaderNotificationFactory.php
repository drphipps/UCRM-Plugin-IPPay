<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\HeaderNotification\Factory;

use AppBundle\Entity\HeaderNotification;

class HeaderNotificationFactory
{
    public function create(
        int $type,
        string $title,
        ?string $description = null,
        ?string $link = null,
        bool $linkTargetBlank = false
    ): HeaderNotification {
        $headerNotification = new HeaderNotification();
        $headerNotification->setType($type);
        $headerNotification->setTitle($title);
        $headerNotification->setDescription($description);
        $headerNotification->setLink($link);
        $headerNotification->setLinkTargetBlank($link && $linkTargetBlank);

        return $headerNotification;
    }
}
