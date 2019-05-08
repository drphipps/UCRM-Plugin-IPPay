<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Tests\Api\Controller;

use Nette\Utils\Json;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobTask;
use Tests\Functional\ApiWebTestCase;

class JobTaskControllerTest extends ApiWebTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $job = new Job();
        $job->setTitle('JobTaskControllerTest');
        $this->em->persist($job);

        $task = new JobTask();
        $task->setLabel('JobTaskControllerTest');
        $task->setJob($job);
        $job->addTask($task);

        $this->em->flush();
    }

    /**
     * @group ApiJobTaskController
     */
    public function testJobTaskGet(): void
    {
        $taskId = $this->getMaxId(JobTask::class);

        $this->client->request(
            'GET',
            sprintf('/api/v1.0/scheduling/jobs/tasks/%d', $taskId)
        );

        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertTrue(
            $this->client->getResponse()->headers->contains('Content-Type', 'application/json')
        );

        $data = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);

        self::assertArrayHasKey('id', $data);
        self::assertSame($taskId, $data['id']);
    }

    /**
     * @group ApiJobTaskController
     */
    public function testJobTasksCollectionGet(): void
    {
        $jobId = $this->getMaxId(Job::class);
        $this->client->request('GET', sprintf('/api/v1.0/scheduling/jobs/tasks?jobId=%d', $jobId));

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertTrue(
            $this->client->getResponse()->headers->contains('Content-Type', 'application/json')
        );

        $data = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);

        self::assertInternalType('array', $data);
        self::assertNotEmpty($data);

        foreach ($data as $item) {
            self::assertInternalType('array', $item);
            self::assertArrayHasKey('id', $item);
            self::assertArrayHasKey('jobId', $item);
            self::assertSame($jobId, $item['jobId']);
        }
    }

    /**
     * @group ApiJobTaskController
     */
    public function testJobPatch()
    {
        $taskId = $this->getMaxId(JobTask::class);
        $jobId = $this->getMaxId(Job::class);
        $data = [
            'jobId' => $jobId,
            'label' => 'title',
            'closed' => true,
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/v1.0/scheduling/jobs/tasks/%d', $taskId),
            [],
            [],
            [],
            Json::encode($data)
        );

        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertTrue(
            $this->client->getResponse()->headers->contains('Content-Type', 'application/json')
        );

        $patchedData = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);
        self::assertSame($taskId, $patchedData['id']);
        self::assertSame($data['jobId'], $patchedData['jobId']);
        self::assertSame($data['label'], $patchedData['label']);
        self::assertSame($data['closed'], $patchedData['closed']);
    }

    /**
     * @group ApiJobTaskController
     */
    public function testJobPostAndDelete()
    {
        $jobId = $this->getMaxId(Job::class);
        $data = [
            'jobId' => $jobId,
            'label' => 'title',
            'closed' => true,
        ];

        $this->client->request(
            'POST',
            '/api/v1.0/scheduling/jobs/tasks',
            [],
            [],
            [],
            Json::encode($data)
        );

        $id = $this->validatePostResponseAndGetId(
            $this->client->getResponse(),
            '~/api/v1.0/scheduling/jobs/tasks/(\d+)$~'
        );
        $task = $this->em->find(
            JobTask::class,
            $id
        );

        self::assertSame($data['jobId'], $task->getJob()->getId());
        self::assertSame($data['label'], $task->getLabel());
        self::assertSame($data['closed'], $task->isClosed());

        $this->client->request('DELETE', sprintf('/api/v1.0/scheduling/jobs/tasks/%d', $task->getId()));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->em->clear();
        self::assertNull($this->em->find(JobTask::class, $task->getId()));
    }
}
