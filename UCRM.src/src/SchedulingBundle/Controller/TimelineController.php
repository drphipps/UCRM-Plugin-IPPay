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
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Security\SchedulingPermissions;
use SchedulingBundle\Service\Facade\JobFacade;
use SchedulingBundle\Service\Job\TimelineDataProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/scheduling/timeline")
 */
class TimelineController extends BaseController implements SchedulingControllerInterface
{
    /**
     * @Route(
     *     "/{filterType}",
     *     name="scheduling_timeline_index",
     *     options={"expose": true},
     *     defaults={"filterType" = "all"},
     *     requirements={"filterType": "all|my"}
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function indexAction(Request $request, string $filterType): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);

        $date = Helpers::getDateFromYMD($request->get('date'));
        $today = new \DateTimeImmutable();
        $date = $date ?? $today;
        $start = $date->modify('midnight');
        $end = $date->modify('+1 day midnight');
        $timelineDataProvider = $this->get(TimelineDataProvider::class);
        $viewAllJobsGranted = $this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL);

        $timelineQueue = $viewAllJobsGranted
            ? $timelineDataProvider->getQueue()
            : null;
        if ($filterType === self::FILTER_MY || ! $viewAllJobsGranted) {
            $user = $this->getUser();
        }

        $timelineGroups = $timelineDataProvider->getGroups($user ?? null);
        $timelineItems = $timelineDataProvider->getItems($start, $end, $user ?? null);

        $filterParameters = [
            'filterType' => $filterType,
            'timeline' => [
                'date' => $date,
                'queue' => $timelineQueue,
            ],
            'forceShowQueue' => $request->query->getBoolean('queue', false),
            'hasQueue' => $this->em->getRepository(Job::class)->hasQueue(),
        ];
        $timelineParameters = [
            'timeline' => [
                'items' => array_merge($timelineItems, []),
                'groups' => array_merge($timelineGroups, []),
                'queue' => $timelineQueue,
                'date' => $date,
                'min' => $start->format(\DateTime::ISO8601),
                'max' => $end->format(\DateTime::ISO8601),
                'start' => $date->setTime(6, 0)->format(\DateTime::ISO8601),
                'end' => $date->setTime(18, 0)->format(\DateTime::ISO8601),
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            // AJAX_IDENTIFIER is used to distinguish between AJAX requests to this URL.
            // For example if paginating we want to render only the items.

            switch ($request->get(SchedulingControllerInterface::AJAX_IDENTIFIER)) {
                case SchedulingControllerInterface::AJAX_IDENTIFIER_PAGINATION:
                    $this->invalidateTemplate(
                        'scheduling-filter',
                        '@Scheduling/components/view/header_buttons_right.html.twig',
                        $filterParameters
                    );

                    return $this->createAjaxResponse(
                        array_merge(
                            [
                                'url' => [
                                    'route' => 'scheduling_timeline_index',
                                    'parameters' => [
                                        'date' => $request->get('date'),
                                        'filterType' => $filterType,
                                    ],
                                ],
                            ],
                            $timelineParameters
                        )
                    );

                case SchedulingControllerInterface::AJAX_IDENTIFIER_FILTER:
                    $this->invalidateTemplate(
                        'scheduling-filter',
                        '@Scheduling/components/view/header_buttons_right.html.twig',
                        $filterParameters
                    );

                    $this->invalidateTemplate(
                        'scheduling-container',
                        '@Scheduling/timeline/components/view/view.html.twig',
                        array_merge(
                            $filterParameters,
                            $timelineParameters
                        )
                    );

                    return $this->createAjaxResponse(
                        [
                            'url' => [
                                'route' => 'scheduling_timeline_index',
                                'parameters' => [
                                    'date' => $request->get('date'),
                                    'filterType' => $filterType,
                                ],
                            ],
                        ]
                    );
            }
        }

        return $this->render(
            '@Scheduling/timeline/index.html.twig',
            array_merge(
                $filterParameters,
                $timelineParameters
            )
        );
    }

    /**
     * @Route(
     *     "/{id}/edit",
     *     name="scheduling_timeline_edit",
     *     requirements={"id": "\d+"},
     *     options={"expose": true}
     * )
     * @Method({"GET", "POST"})
     * @CsrfToken(methods={"GET", "POST"})
     * @Permission("guest")
     */
    public function editAction(Request $request, Job $job): Response
    {
        $jobBeforeEdit = clone $job;
        $item = $request->get('item');
        if (! $item || (int) $item['id'] !== $job->getId()) {
            throw $this->createNotFoundException();
        }

        if (
            ! $job->getAssignedUser()
            || $job->getAssignedUser() !== $this->getUser()
            || (int) ($item['group'] ?? 0) !== $this->getUser()->getId()
        ) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL);
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_MY);
            unset($item['group']);
        }

        $this->get(JobFacade::class)->handleTimelineEdit(
            $request->get('item'),
            $job,
            $jobBeforeEdit
        );

        return new Response();
    }
}
