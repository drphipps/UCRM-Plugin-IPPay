<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Tests\Controller;

use Nette\Utils\Json;
use SchedulingBundle\Entity\Job;
use Tests\Functional\AdminWebTestCase;

class JobControllerTest extends AdminWebTestCase
{
    /**
     * @group JobController
     * @dataProvider urlNotFoundProvider
     */
    public function testPageIsNotFound(string $url): void
    {
        $this->client->followRedirects(false);
        $this->client->request('GET', $url);

        self::assertTrue($this->client->getResponse()->isNotFound());
    }

    public function urlNotFoundProvider(): array
    {
        return [
            ['/scheduling/job/1aaa'],
        ];
    }

    /**
     * @group JobController
     */
    public function testCrud(): void
    {
        $this->client->followRedirects(false);

        // test new
        $crawler = $this->client->request('GET', '/scheduling/job/new');
        self::assertTrue($this->client->getResponse()->isSuccessful());
        $form = $crawler->filter('#job-form')->form();

        $this->client->submit(
            $form,
            [
                'job[title]' => 'Test job',
            ]
        );

        self::assertTrue($this->client->getResponse()->isRedirect());

        $job = $this->em->getRepository(Job::class)->findOneBy(
            [
                'title' => 'Test job',
            ]
        );
        self::assertNotNull($job);

        // test edit
        $crawler = $this->client->request('GET', sprintf('/scheduling/job/%d/edit', $job->getId()));
        self::assertTrue($this->client->getResponse()->isSuccessful());
        $form = $crawler->filter('#job-form')->form();

        $this->client->submit(
            $form,
            [
                'job[title]' => 'Test job edit',
            ]
        );

        self::assertTrue($this->client->getResponse()->isRedirect());
        $job = $this->em->getRepository(Job::class)->findOneBy(
            [
                'title' => 'Test job edit',
            ]
        );
        self::assertNotNull($job);

        // test show
        $this->client->request('GET', sprintf('/scheduling/job/%d', $job->getId()));
        self::assertTrue($this->client->getResponse()->isSuccessful());

        // test delete
        $this->client->request('GET', sprintf('/scheduling/job/%d/delete', $job->getId()));
        self::assertTrue($this->client->getResponse()->isRedirect());
        $job = $this->em->getRepository(Job::class)->findOneBy(
            [
                'title' => 'Test job edit',
            ]
        );
        self::assertNull($job);
    }

    /**
     * @group JobController
     */
    public function testNewCommentAction(): void
    {
        $this->client->followRedirects(false);

        // create a job
        $crawler = $this->client->request('GET', '/scheduling/job/new');
        self::assertTrue($this->client->getResponse()->isSuccessful());
        $form = $crawler->filter('#job-form')->form();

        $this->client->submit(
            $form,
            [
                'job[title]' => 'JobControllerTest::testNewCommentAction',
            ]
        );

        self::assertTrue($this->client->getResponse()->isRedirect());

        $job = $this->em->getRepository(Job::class)->findOneBy(
            [
                'title' => 'JobControllerTest::testNewCommentAction',
            ]
        );
        self::assertNotNull($job);

        // add a comment
        $crawler = $this->client->request('GET', sprintf('/scheduling/job/%d/comments/new', $job->getId()));
        self::assertTrue($this->client->getResponse()->isSuccessful());
        $form = $crawler->filter('#new-comment-form')->form();

        $this->client->submit(
            $form,
            [
                'job_comment[message]' => 'JobControllerTest::testNewCommentAction__message',
            ]
        );

        self::assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );

        $data = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);
        self::assertTrue(isset($data['flashBag']['success']));

        $this->client->request('GET', sprintf('/scheduling/job/%d', $job->getId()));
        self::assertContains(
            'JobControllerTest::testNewCommentAction__message',
            $this->client->getResponse()->getContent()
        );
    }
}
