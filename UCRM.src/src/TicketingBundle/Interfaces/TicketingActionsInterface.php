<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Interfaces;

use TicketingBundle\Component\TicketingRoutesMap;

interface TicketingActionsInterface
{
    public const AJAX_IDENTIFIER = 'ajax-identifier';
    public const AJAX_IDENTIFIER_PAGINATION = 'ticketing-paginator';
    public const AJAX_IDENTIFIER_FILTER = 'ticketing-filter';
    public const AJAX_IDENTIFIER_DETAIL = 'ticketing-detail';

    public const ITEMS_PER_PAGE = 10;
    public const SEARCH_ITEMS_LIMIT = 100;

    public const USER_FILTER_ALL = 'all';
    public const USER_FILTER_MY = 'my';
    public const USER_FILTER_UNASSIGNED = 'unassigned';
    public const POSSIBLE_USER_FILTERS = [
        self::USER_FILTER_ALL,
        self::USER_FILTER_MY,
        self::USER_FILTER_UNASSIGNED,
    ];

    public const PERSISTENT_PARAMETERS = [
        'search',
        'status-filters',
        'user-filter',
        'last-activity-filter',
    ];

    public const LAST_ACTIVITY_FILTER_ALL = 'all';
    public const LAST_ACTIVITY_FILTER_CLIENT = 'client';
    public const LAST_ACTIVITY_FILTER_ADMIN = 'admin';

    public const FORM_TAB_REPLY = 'reply';
    public const FORM_TAB_ADD_JOB = 'add-job';
    public const FORM_TAB_LINKED_JOBS = 'linked-jobs';

    public function getTicketingRoutesMap(): TicketingRoutesMap;
}
