<?php

namespace Tests\Functional;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class DeniedAdminWebTestCase extends UcrmWebTestCase
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
    }

    /**
     * @beforeClass
     */
    public static function beforeClass()
    {
        static::bootKernel();

        $server = [
            'PHP_AUTH_PW' => getenv('UCRM_ADMIN_USERNAME') ?: User::USER_DENIED_ADMIN,
            'PHP_AUTH_USER' => getenv('UCRM_ADMIN_PASSWORD') ?: 'denied_admin',
        ];

        if (getenv('UCRM_HOST')) {
            $server['HTTP_HOST'] = getenv('UCRM_HOST');
        }

        self::$clientStatic = static::createClient([], $server);
        self::$clientStatic->followRedirects();

        self::$entityManager = static::$kernel->getContainer()->get(EntityManager::class);
    }
}
