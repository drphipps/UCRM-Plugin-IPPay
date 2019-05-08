<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Client;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class ActionLogger
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityManagerRecreator
     */
    private $emRecreator;

    public function __construct(EntityManager $em, EntityManagerRecreator $emRecreator)
    {
        $this->em = $em;
        $this->emRecreator = $emRecreator;
    }

    public function log(array $message, ?User $user, ?Client $client, int $changeType): void
    {
        $log = new EntityLog();
        $log->setCreatedDate(new \DateTime());
        $log->setLog($this->getMessage($message));
        $log->setClient($client);
        $log->setChangeType($changeType);
        $log->setUserType($this->getUserType($user));

        if (! $this->em->isOpen()) {
            $this->em = $this->emRecreator->create($this->em);

            if ($user) {
                $user = $this->em->merge($user);
            }
        }

        $log->setUser($user);

        $this->em->persist($log);
        $this->em->flush();
    }

    private function getMessage(array $message): string
    {
        return serialize($message);
    }

    private function getUserType(?User $user): int
    {
        if (! $user) {
            return EntityLog::SYSTEM;
        }

        if ($user->getClient()) {
            return EntityLog::CLIENT;
        }

        return EntityLog::ADMIN;
    }
}
