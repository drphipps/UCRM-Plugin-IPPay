<?php

namespace Tests\Functional;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class AdminWebTestCase extends UcrmWebTestCase
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
        $this->client = self::$clientStatic;
        $this->em = self::$entityManager;

        $this->client->followRedirects();
    }

    /**
     * @beforeClass
     */
    public static function beforeClass()
    {
        $server = [
            'PHP_AUTH_PW' => getenv('UCRM_ADMIN_USERNAME') ?: 'admin',
            'PHP_AUTH_USER' => getenv('UCRM_ADMIN_PASSWORD') ?: 'admin',
        ];

        if (getenv('UCRM_HOST')) {
            $server['HTTP_HOST'] = getenv('UCRM_HOST');
        }

        self::$clientStatic = static::createClient([], $server);
        self::$clientStatic->followRedirects();
        self::$clientStatic->setMaxRedirects(5);

        self::$entityManager = static::$kernel->getContainer()->get(EntityManager::class);
    }
}
