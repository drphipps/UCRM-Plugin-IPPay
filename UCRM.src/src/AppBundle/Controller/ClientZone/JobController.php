<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller\ClientZone;

use AppBundle\Security\Permission;
use SchedulingBundle\Entity\Job;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client-zone/job")
 */
class JobController extends BaseController
{
    /**
     * @Route("/{id}", name="client_zone_job_show", requirements={"id": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function showAction(Job $job): Response
    {
        $this->verifyOwnership($job);

        return $this->render(
            'client_zone/job/job_show_modal.html.twig',
            [
                'job' => $job,
            ]
        );
    }
}
