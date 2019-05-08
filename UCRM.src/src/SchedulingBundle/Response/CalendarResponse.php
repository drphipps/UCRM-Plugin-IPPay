<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Response;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CalendarResponse extends Response
{
    public function __construct(string $calendar)
    {
        parent::__construct($calendar);

        $this->headers->set('Content-Type', 'text/calendar');

        $disposition = $this->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'cal.ics'
        );
        $this->headers->set('Content-Disposition', $disposition);
    }
}
