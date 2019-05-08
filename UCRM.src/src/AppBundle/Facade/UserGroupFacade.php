<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Controller\PaymentController;
use AppBundle\Entity\UserGroup;
use AppBundle\Event\User\UserArchiveEvent;
use AppBundle\Security\Permission;
use AppBundle\Security\SpecialPermission;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use TransactionEventsBundle\TransactionDispatcher;

class UserGroupFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(EntityManagerInterface $entityManager, TransactionDispatcher $transactionDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleDelete(UserGroup $userGroup): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($userGroup) {
                foreach ($userGroup->getUsers() as $user) {
                    $user->setDeletedAt(new \DateTime());
                    yield new UserArchiveEvent($user);
                }

                $this->entityManager->remove($userGroup);
            }
        );
    }

    public function handleUpdate(UserGroup $userGroup): void
    {
        $this->fixPaymentControllerPermission($userGroup);
        $this->entityManager->persist($userGroup);
        $this->entityManager->flush();
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(UserGroup::class)->createQueryBuilder('ug');
    }

    private function fixPaymentControllerPermission(UserGroup $userGroup): void
    {
        if (
            $userGroup->getPermission(PaymentController::class)->getPermission()
            === Permission::DENIED
        ) {
            $userGroup->getSpecialPermission(SpecialPermission::PAYMENT_CREATE)->setPermission(
                SpecialPermission::DENIED
            );
        }
    }
}
