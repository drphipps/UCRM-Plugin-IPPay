<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\HeaderNotification;

use AppBundle\Component\HeaderNotification\Factory\HeaderNotificationFactory;
use AppBundle\Entity\User;

class HeaderNotifier
{
    /**
     * @var HeaderNotificationSender
     */
    private $headerNotificationSender;

    /**
     * @var HeaderNotificationFactory
     */
    private $headerNotificationFactory;

    public function __construct(
        HeaderNotificationSender $headerNotificationSender,
        HeaderNotificationFactory $headerNotificationFactory
    ) {
        $this->headerNotificationSender = $headerNotificationSender;
        $this->headerNotificationFactory = $headerNotificationFactory;
    }

    public function sendToAllAdmins(
        int $type,
        string $title,
        ?string $description = null,
        ?string $link = null,
        bool $linkTargetBlank = false
    ): void {
        $this->headerNotificationSender->sendToAllAdmins(
            $this->headerNotificationFactory->create(
                $type,
                $title,
                $description,
                $link,
                $linkTargetBlank
            )
        );
    }

    public function sendToAdmin(
        User $user,
        int $type,
        string $title,
        ?string $description = null,
        ?string $link = null,
        bool $linkTargetBlank = false
    ): void {
        $this->headerNotificationSender->sendToAdmin(
            $this->headerNotificationFactory->create(
                $type,
                $title,
                $description,
                $link,
                $linkTargetBlank
            ),
            $user
        );
    }

    public function sendByPermission(
        string $permissionName,
        int $type,
        string $title,
        ?string $description = null,
        ?string $link = null,
        bool $linkTargetBlank = false
    ): void {
        $this->headerNotificationSender->sendByPermission(
            $this->headerNotificationFactory->create(
                $type,
                $title,
                $description,
                $link,
                $linkTargetBlank
            ),
            $permissionName
        );
    }
}
