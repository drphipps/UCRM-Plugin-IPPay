<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Service;

class PeriodDataUsageData
{
    /**
     * @var float
     */
    public $download;

    /**
     * @var float
     */
    public $upload;

    /**
     * @var float
     */
    public $downloadLimit;

    /**
     * @var string
     */
    public $unit;

    /**
     * @var \DateTimeImmutable|null
     */
    public $startDate;

    /**
     * @var \DateTimeImmutable|null
     */
    public $endDate;
}
