<?php

namespace Tests\Functional;

use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class AnonymousWebTestCase extends UcrmWebTestCase
{
    /**
     * @var Client
     */
    private static $clientStatic;

    /**
     * @var EntityManager
     */
    private static $entityManager;

    protected function setUp()
    {
        $server = [];
        if (getenv('UCRM_HOST')) {
            $server['HTTP_HOST'] = getenv('UCRM_HOST');
        }
        self::$clientStatic = static::createClient([], $server);
        self::$clientStatic->followRedirects();

        self::$entityManager = static::$kernel->getContainer()->get(EntityManager::class);

        $this->client = self::$clientStatic;
        $this->em = self::$entityManager;

        $optionRepository = $this->em->getRepository(Option::class);
        $serverIp = $optionRepository->findOneBy(
            [
                'code' => Option::SERVER_IP,
            ]
        );
        $serverIp->setValue('127.0.0.1');
        $this->em->flush();
        $this->client->getContainer()->get(Options::class)->refresh();
    }

    protected function tearDown()
    {
        $optionRepository = $this->em->getRepository(Option::class);
        $serverIp = $optionRepository->findOneBy(
            [
                'code' => Option::SERVER_IP,
            ]
        );
        $serverIp->setValue('');
        $this->em->flush();
        $this->client->getContainer()->get(Options::class)->refresh();

        parent::tearDown();
    }
}
