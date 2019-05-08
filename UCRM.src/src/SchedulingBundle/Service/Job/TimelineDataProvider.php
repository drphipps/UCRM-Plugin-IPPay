<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Service\Job;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use SchedulingBundle\Entity\Job;

class TimelineDataProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var JobToVisArrayConverter
     */
    private $jobToVisArrayConverter;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        EntityManager $em,
        JobToVisArrayConverter $jobToVisArrayConverter,
        \Twig_Environment $twig
    ) {
        $this->em = $em;
        $this->jobToVisArrayConverter = $jobToVisArrayConverter;
        $this->twig = $twig;
    }

    public function getItems(\DateTimeImmutable $start, \DateTimeImmutable $end, ?User $user = null): array
    {
        $jobs = $this->em->getRepository(Job::class)->getByDateRange($start, $end, $user);

        $items = [];
        foreach ($jobs as $job) {
            $items[] = $this->jobToVisArrayConverter->convert($job);
        }

        return $items;
    }

    public function getGroups(?User $user): array
    {
        if ($user) {
            return [
                $user->getId() => [
                    'id' => $user->getId(),
                    'content' => $this->twig->render(
                        '@Scheduling/timeline/components/view/timeline_user.html.twig',
                        [
                            'name' => $user->getNameForView(),
                            'avatarColor' => $user->getAvatarColor(),
                        ]
                    ),
                ],
            ];
        }

        $admins = $this->em->getRepository(User::class)->findAllAdminsForTimeline();
        $groups = [];
        foreach ($admins as $id => $row) {
            /** @var User $user */
            $user = $row[0];
            $groups[$id] = [
                'id' => $id,
                'content' => $this->twig->render(
                    '@Scheduling/timeline/components/view/timeline_user.html.twig',
                    [
                        'name' => $user->getFullName(),
                        'avatarColor' => $row['avatar_color'],
                    ]
                ),
            ];
        }

        return $groups;
    }

    public function getQueue(): array
    {
        $jobs = $this->em->getRepository(Job::class)->getQueue();

        $queue = [];
        foreach ($jobs as $job) {
            $queue[$job->getId()] = [
                'job' => $job,
                'timelineData' => $this->jobToVisArrayConverter->convert($job),
            ];
        }

        return $queue;
    }
}
