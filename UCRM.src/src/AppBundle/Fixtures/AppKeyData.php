<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Fixtures;

use AppBundle\Entity\AppKey;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class AppKeyData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $em)
    {
        assert($em instanceof EntityManager);

        $this->loadAppKeyWithRead($em);
        $this->loadAppKeyWithWrite($em);

        $em->flush();
    }

    public function getOrder()
    {
        return 1;
    }

    private function loadAppKeyWithRead(EntityManager $em): void
    {
        $key = new AppKey();
        $key->setName('Test app key read');
        $key->setKey('BvBdsGHQKc1dOOWGMcy0f07+2czCOb90zv5zxHNDhf4P5NFElwKsZWWV3QceKq5J');
        $key->setType(AppKey::TYPE_READ);
        $key->setCreatedDate(new \DateTime());

        $em->persist($key);
    }

    private function loadAppKeyWithWrite(EntityManager $em): void
    {
        $key = new AppKey();
        $key->setName('Test app key write');
        $key->setKey('5YbpCSto7ffl/P/veJ/GK3U7K7zH6ZoHil7j5dorerSN8o+rlJJq6X/uFGZQF2WL');
        $key->setType(AppKey::TYPE_WRITE);
        $key->setCreatedDate(new \DateTime());

        $em->persist($key);
    }
}
