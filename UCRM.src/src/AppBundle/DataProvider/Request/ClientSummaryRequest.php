<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider\Request;

class ClientSummaryRequest
{
    /**
     * @var bool|null
     */
    public $getRelatedInvoices;

    /**
     * @var bool|null
     */
    public $getRelatedServices;

    /**
     * @var string|null
     */
    public $direction;

    /**
     * @var bool|null
     */
    public $isLead;

    /**
     * @var int|null
     */
    public $limit;

    /**
     * @var int|null
     */
    public $offset;

    /**
     * @var string|null
     */
    public $order;

    /**
     * @var bool|null
     */
    public $outage;

    /**
     * @var bool|null
     */
    public $overdue;

    /**
     * @var bool|null
     */
    public $suspended;
}
