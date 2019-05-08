<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Controller;

interface SchedulingControllerInterface
{
    public const AJAX_IDENTIFIER = 'ajax-identifier';
    public const AJAX_IDENTIFIER_PAGINATION = 'scheduling-paginator';
    public const AJAX_IDENTIFIER_FILTER = 'scheduling-filter';

    public const FILTER_ALL = 'all';
    public const FILTER_MY = 'my';
}
