<?php

namespace Tests\Functional;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;

abstract class ContainerTestCase extends KernelTestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $em;

    protected function setUp()
    {
        /** @var Kernel|null $kernel */
        $kernel = static::$kernel;
        if (! $kernel) {
            static::bootKernel();
        }
        static::$kernel->boot();

        $this->container = static::$kernel->getContainer();

        $this->em = $this->container->get(EntityManager::class);
        $this->em->clear();
    }

    protected function tearDown()
    {
        // Don't call parent to prevent kernel reboot.
    }

    protected static function getKernelClass()
    {
        return \AppKernel::class;
    }
}
