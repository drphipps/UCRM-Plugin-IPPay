<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Entity\User;
use AppBundle\Security\Permission;
use SchedulingBundle\Response\CalendarResponse;
use SchedulingBundle\Security\SchedulingPermissions;
use SchedulingBundle\Service\Job\ICalExportGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/scheduling/calendar-export")
 */
class CalendarExportController extends BaseController
{
    /**
     * @Route("/ical/{id}", name="scheduling_calendar_export_ical")
     * @Method("GET")
     * @Permission("guest")
     */
    public function icalAction(User $user): Response
    {
        if ($user !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);

        return new CalendarResponse($this->get(ICalExportGenerator::class)->getByUser($user));
    }
}
