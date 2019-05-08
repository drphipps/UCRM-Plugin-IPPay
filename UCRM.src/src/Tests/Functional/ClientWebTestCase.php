<?php

namespace Tests\Functional;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class ClientWebTestCase extends UcrmWebTestCase
{
    const CLIENT_USER_ID = 1000;
    const CLIENT_PASSWORD = '$2y$12$PPkJBc2wCWCMZnN40gIc7uJZ5rc/TAgdJ7rMS48ZRRScbGi.h8GE.'; // client

    /**
     * @var Client
     */
    protected static $clientStatic;

    /**
     * @var EntityManager
     */
    protected static $entityManager;

    protected function setUp()
    {
        $this->client = self::$clientStatic;
        $this->em = self::$entityManager;
    }

    /**
     * @beforeClass
     */
    public static function beforeClass()
    {
        $server = [
            'PHP_AUTH_PW' => getenv('UCRM_CLIENT_USERNAME') ?: 'client',
            'PHP_AUTH_USER' => getenv('UCRM_CLIENT_PASSWORD') ?: 'client',
        ];

        if (getenv('UCRM_HOST')) {
            $server['HTTP_HOST'] = getenv('UCRM_HOST');
        }

        self::$clientStatic = static::createClient([], $server);
        self::$clientStatic->followRedirects();

        self::$entityManager = static::$kernel->getContainer()->get(EntityManager::class);
    }
}
