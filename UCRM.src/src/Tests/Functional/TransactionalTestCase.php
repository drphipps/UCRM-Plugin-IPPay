<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace Tests\Functional;

use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Doctrine\DBAL\Connection;

abstract class TransactionalTestCase extends ContainerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->em->clear();
        $this->container->get(Options::class)->reset();

        $this->em->getConnection()->setTransactionIsolation(Connection::TRANSACTION_SERIALIZABLE);
        $this->em->beginTransaction();
    }

    protected function tearDown()
    {
        $this->em->rollback();

        parent::tearDown();
    }

    protected function clearAndReload(&...$entities): void
    {
        $this->em->clear();

        foreach ($entities as &$entity) {
            $entity = $this->em->merge($entity);
            $this->em->refresh($entity);
        }
    }

    protected function updateOption(string $code, string $value): Option
    {
        $option = $this->em->getRepository(Option::class)->findOneBy(['code' => $code]);
        $option->setValue($value);
        $this->em->flush($option);
        $this->container->get(Options::class)->refresh();

        return $option;
    }
}
