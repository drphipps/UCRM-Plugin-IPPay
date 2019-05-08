<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Tests\Controller;

use Nette\Utils\Json;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobTask;
use Tests\Functional\AdminWebTestCase;

class JobTaskControllerTest extends AdminWebTestCase
{
    /**
     * @group JobTaskController
     */
    public function testCrud(): void
    {
        $this->client->followRedirects(false);

        // create new job
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

        // new task
        $this->client->request(
            'GET',
            sprintf(
                '/scheduling/job/task/new/%d?label=%s',
                $job->getId(),
                'Lorem ipsum'
            )
        );
        self::assertTrue($this->client->getResponse()->isSuccessful());
        $response = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);
        self::assertArrayHasKey('task', $response);
        self::assertArrayHasKey('id', $response['task']);
        self::assertSame('Lorem ipsum', $response['task']['label']);
        self::assertSame(false, $response['task']['closed']);

        $taskId = (int) $response['task']['id'];

        // close task
        $this->client->request(
            'GET',
            sprintf(
                '/scheduling/job/task/%d/change-closed?closed=%d',
                $taskId,
                1
            )
        );
        self::assertTrue($this->client->getResponse()->isSuccessful());

        // edit task
        $this->client->request(
            'GET',
            sprintf(
                '/scheduling/job/task/%d/edit?label=%s',
                $taskId,
                'Dolor sit amet'
            )
        );
        self::assertTrue($this->client->getResponse()->isSuccessful());
        $response = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);
        self::assertArrayHasKey('task', $response);
        self::assertSame($taskId, $response['task']['id']);
        self::assertSame('Dolor sit amet', $response['task']['label']);
        self::assertSame(true, $response['task']['closed']);

        // open task
        $this->client->request(
            'GET',
            sprintf(
                '/scheduling/job/task/%d/change-closed?closed=%d',
                $taskId,
                0
            )
        );
        self::assertTrue($this->client->getResponse()->isSuccessful());

        // delete task
        $this->client->request(
            'GET',
            sprintf(
                '/scheduling/job/task/%d/delete',
                $taskId
            )
        );
        self::assertTrue($this->client->getResponse()->isSuccessful());

        $this->em->clear();
        self::assertNull($this->em->getRepository(JobTask::class)->find($taskId));
    }
}
