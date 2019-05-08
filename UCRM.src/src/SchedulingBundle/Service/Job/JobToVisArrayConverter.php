<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Service\Job;

use AppBundle\Security\Permission;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Security\SchedulingPermissions;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class JobToVisArrayConverter
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage
    ) {
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    public function convert(Job $job): array
    {
        $start = $job->getDate()
            ? $job->getDate()->format(\DateTime::ISO8601)
            : null;
        $end = $start && $job->getDuration()
            ? (clone $job->getDate())
                ->modify(sprintf('+%d minutes', $job->getDuration()))
                ->format(\DateTime::ISO8601)
            : null;

        if ($this->authorizationChecker->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL)) {
            $editable = [
                'remove' => false,
                'updateGroup' => true,
                'updateTime' => true,
            ];
        } elseif ($this->authorizationChecker->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_MY)) {
            $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;

            $editable = [
                'remove' => false,
                'updateGroup' => false,
                'updateTime' => $user && $job->getAssignedUser() && $job->getAssignedUser() === $user,
            ];
        } else {
            $editable = false;
        }

        return [
            'id' => $job->getId(),
            'content' => $job->getTitle(),
            'start' => $start,
            'end' => $end,
            'type' => $job->getDuration() ? 'range' : 'box',
            'group' => $job->getAssignedUser() ? $job->getAssignedUser()->getId() : null,
            'duration' => $job->getDuration(),
            'className' => sprintf('status--%s', Job::STATUS_CLASSES[$job->getStatus()]),
            'status' => $this->translator->trans(Job::STATUSES[$job->getStatus()]),
            'editable' => $editable,
        ];
    }
}
