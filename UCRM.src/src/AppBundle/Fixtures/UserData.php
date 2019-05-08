<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Fixtures;

use AppBundle\Entity\User;
use AppBundle\Entity\UserGroup;
use AppBundle\Security\LoginSuccessHandler;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class UserData extends BaseFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $em)
    {
        assert($em instanceof EntityManager);
        $this->loadSuperAdmin($em);
        $this->loadOrdinaryAdmin($em);

        $em->flush();
    }

    public function getOrder()
    {
        return 2;
    }

    private function loadSuperAdmin(EntityManager $em): void
    {
        $user = new User();
        $user->setRole(User::ROLE_SUPER_ADMIN);
        $user->setUsername(User::USER_ADMIN);
        $user->setPassword(User::USER_ADMIN_PASSWORD);

        $em->persist($user);
    }

    private function loadOrdinaryAdmin(EntityManager $em): void
    {
        $user = new User();
        $user->setRole(User::ROLE_ADMIN);
        $user->setUsername(User::USER_ORDINARY_ADMIN);
        $user->setPassword(User::USER_ORDINARY_ADMIN_PASSWORD);
        $user->setGroup($em->getReference(UserGroup::class, 1));
        $user->setEmail('ordinary.admin@example.com');
        $user->setIsActive(true);
        $user->setFirstName('Ordinary');
        $user->setLastName('Doe');

        $em->persist($user);
    }
}
