<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class ServiceUsageMap extends AbstractMap
{
    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $download;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $upload;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $downloadLimit;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $unit;

    /**
     * @Type("DateTimeImmutable")
     * @ReadOnly()
     */
    public $startDate;

    /**
     * @Type("DateTimeImmutable")
     * @ReadOnly()
     */
    public $endDate;
}
