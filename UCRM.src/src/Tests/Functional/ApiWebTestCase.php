<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace Tests\Functional;

use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiWebTestCase extends WebTestCase
{
    const APP_KEY_READ = 'BvBdsGHQKc1dOOWGMcy0f07+2czCOb90zv5zxHNDhf4P5NFElwKsZWWV3QceKq5J';
    const APP_KEY_WRITE = '5YbpCSto7ffl/P/veJ/GK3U7K7zH6ZoHil7j5dorerSN8o+rlJJq6X/uFGZQF2WL';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var EntityManager
     */
    protected $em;

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
        $this->em->flush($serverIp);
        $this->client->getContainer()->get(Options::class)->refresh();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');
        $this->client->setServerParameter('HTTP_X_AUTH_APP_KEY', self::APP_KEY_WRITE);
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
        $this->em->flush($serverIp);
        $this->client->getContainer()->get(Options::class)->refresh();

        parent::tearDown();
    }

    protected function getMaxId(string $className, bool $isSoftdeletable = false): int
    {
        $qb = $this->em->getRepository($className)->createQueryBuilder('s');
        if ($isSoftdeletable) {
            $qb->where('s.deletedAt IS NULL');
        }

        return (int) $qb->select('MAX(s.id)')->getQuery()->getSingleScalarResult();
    }

    protected function validatePostResponseAndGetId(
        Response $response,
        string $redirectUrlPattern
    ): int {
        self::assertSame(201, $response->getStatusCode());
        self::assertTrue($response->isRedirect());

        /** @var string $redirectUrl */
        $redirectUrl = $response->headers->get('Location');
        self::assertRegExp($redirectUrlPattern, $redirectUrl);

        self::assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $data = Json::decode($response->getContent(), Json::FORCE_ARRAY);
        self::assertArrayHasKey('id', $data);
        self::assertSame((int) $data['id'], $this->extractIdFromUrl($redirectUrl, $redirectUrlPattern));

        return (int) $data['id'];
    }

    private function extractIdFromUrl(string $url, string $urlPattern): ?int
    {
        $matches = Strings::match($url, $urlPattern);

        return $matches ? (int) $matches[1] : null;
    }

    protected static function getKernelClass()
    {
        return \AppKernel::class;
    }
}
