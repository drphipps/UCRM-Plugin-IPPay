<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Csv\RowData;

class ClientLogsViewRowData
{
    /**
     * @var int
     */
    public $logId;

    /**
     * @var string
     */
    public $logType;

    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $createdDate;

    /**
     * @var string
     */
    public $user;

    /**
     * @var string
     */
    public $entityLogDetails;
}
