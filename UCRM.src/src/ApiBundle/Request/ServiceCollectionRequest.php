<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Request;

class ServiceCollectionRequest
{
    /**
     * @var int|null
     */
    public $organizationId;

    /**
     * @var int|null
     */
    public $clientId;

    /**
     * @var int[]|null
     */
    public $statuses;

    /**
     * @var int|null
     */
    public $limit;

    /**
     * @var int|null
     */
    public $offset;
}
