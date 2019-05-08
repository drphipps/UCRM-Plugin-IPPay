<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Security\Permission;
use AppBundle\Util\Helpers;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Grid\Job\JobGridFactory;
use SchedulingBundle\Grid\MyJobs\MyJobsGridFactory;
use SchedulingBundle\Security\SchedulingPermissions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/scheduling/agenda")
 */
class AgendaController extends BaseController implements SchedulingControllerInterface
{
    /**
     * @Route(
     *     "/{filterType}",
     *     name="scheduling_agenda_index",
     *     defaults={"filterType" = "all"},
     *     requirements={"filterType": "all|my"}
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function indexAction(Request $request, string $filterType): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);

        if (
            $this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL)
            && $filterType === self::FILTER_ALL
        ) {
            $grid = $this->get(JobGridFactory::class)->create();
        } else {
            $grid = $this->get(MyJobsGridFactory::class)->create($this->getUser());
        }

        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            '@scheduling/agenda/index.html.twig',
            [
                'filterType' => $filterType,
                'grid' => $grid,
                'timeline' => [
                    'date' => Helpers::getDateFromYMD($grid->getActiveFilter('date_from')),
                ],
                'hasQueue' => $this->em->getRepository(Job::class)->hasQueue(),
            ]
        );
    }
}
