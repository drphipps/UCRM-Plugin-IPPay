<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Service\Job;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\Geo;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Repository\JobRepository;

class ICalExportGenerator
{
    /**
     * @var EntityRepository|JobRepository
     */
    private $jobRepository;

    public function __construct(EntityManager $em)
    {
        $this->jobRepository = $em->getRepository(Job::class);
    }

    public function getByUser(User $user): string
    {
        $calendar = new Calendar('Ubiquiti Networks, Inc.//UCRM - Complete WISP Management Platform');

        $jobs = $this->jobRepository->getByUser($user);
        foreach ($jobs as $job) {
            $calendar->addComponent($this->createEvent($job));
        }

        return $calendar->render();
    }

    private function createEvent(Job $job): Event
    {
        $event = new Event();
        $event->setDtStart(clone $job->getDate());
        $end = clone $job->getDate();
        if (null !== $job->getDuration()) {
            $end->modify(sprintf('+%d minutes', $job->getDuration()));
        }
        $event->setDtEnd($end);

        $event->setDescription($job->getDescription() ?? '');
        $event->setSummary($job->getTitle());
        $event->setUseTimezone(true);

        if ($job->getAddress()) {
            if ($job->getGpsLat() && $job->getGpsLon()) {
                $geo = new Geo((float) $job->getGpsLat(), (float) $job->getGpsLon());
            }

            $event->setLocation($job->getAddress(), '', $geo ?? null);
        }

        return $event;
    }
}
