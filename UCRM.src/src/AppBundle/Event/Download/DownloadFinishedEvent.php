<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Download;

use AppBundle\Entity\Download;
use Symfony\Component\EventDispatcher\Event;

class DownloadFinishedEvent extends Event
{
    /**
     * @var Download
     */
    private $download;

    /**
     * @var bool
     */
    private $sendNotification;

    public function __construct(Download $download, bool $sendNotification)
    {
        $this->download = $download;
        $this->sendNotification = $sendNotification;
    }

    public function getDownload(): Download
    {
        return $this->download;
    }

    public function getSendNotification(): bool
    {
        return $this->sendNotification;
    }
}
