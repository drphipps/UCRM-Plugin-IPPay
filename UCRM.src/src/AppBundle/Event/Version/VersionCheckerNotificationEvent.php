<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Version;

use Symfony\Component\EventDispatcher\Event;

class VersionCheckerNotificationEvent extends Event
{
    /**
     * @var string
     */
    private $channel;

    /**
     * @var string
     */
    private $version;

    public function __construct($channel, $version)
    {
        $this->channel = $channel;
        $this->version = $version;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
