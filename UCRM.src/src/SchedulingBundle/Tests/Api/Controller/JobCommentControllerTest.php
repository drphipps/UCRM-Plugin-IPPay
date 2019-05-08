<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Tests\Api\Controller;

use Nette\Utils\Json;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobComment;
use Tests\Functional\ApiWebTestCase;

class JobCommentControllerTest extends ApiWebTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $job = new Job();
        $job->setTitle('JobCommentControllerTest');
        $this->em->persist($job);

        $jobComment = new JobComment();
        $jobComment->setJob($job);
        $jobComment->setMessage('JobCommentControllerTest');
        $this->em->persist($jobComment);

        $this->em->flush();
    }

    /**
     * @group ApiJobCommentController
     */
    public function testJobCommentsGet(): void
    {
        $jobCommentId = $this->getMaxId(JobComment::class);

        $this->client->request(
            'GET',
            sprintf('/api/v1.0/scheduling/jobs/comments/%d', $jobCommentId)
        );

        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertTrue(
            $this->client->getResponse()->headers->contains('Content-Type', 'application/json')
        );

        $data = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);

        self::assertArrayHasKey('id', $data);
        self::assertSame($jobCommentId, $data['id']);
    }

    /**
     * @group ApiJobCommentController
     */
    public function testJobCommentsCollectionGet(): void
    {
        $this->client->request('GET', '/api/v1.0/scheduling/jobs/comments');

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
        }
    }

    /**
     * @group ApiJobCommentController
     */
    public function testJobCommentPatch()
    {
        $jobId = $this->getMaxId(Job::class);
        $jobCommentId = $this->getMaxId(JobComment::class);
        $data = [
            'message' => 'message',
            'userId' => 1,
            'jobId' => $jobId,
            'createdDate' => '2017-05-17T11:00:00+0200',
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/v1.0/scheduling/jobs/comments/%d', $jobCommentId),
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
        self::assertSame($jobCommentId, $patchedData['id']);
        self::assertSame($data['message'], $patchedData['message']);
        self::assertSame($data['userId'], $patchedData['userId']);
        self::assertSame($data['jobId'], $patchedData['jobId']);
        self::assertSame($data['createdDate'], $patchedData['createdDate']);
    }

    /**
     * @group ApiJobCommentController
     */
    public function testJobCommentPostAndDelete()
    {
        $jobId = $this->getMaxId(Job::class);
        $data = [
            'message' => 'message',
            'userId' => 1,
            'jobId' => $jobId,
            'createdDate' => '2017-05-17T11:00:00+0200',
        ];

        $this->client->request(
            'POST',
            '/api/v1.0/scheduling/jobs/comments',
            [],
            [],
            [],
            Json::encode($data)
        );

        $id = $this->validatePostResponseAndGetId(
            $this->client->getResponse(),
            '~/api/v1.0/scheduling/jobs/comments/(\d+)$~'
        );
        $jobComment = $this->em->find(
            JobComment::class,
            $id
        );
        $jobCommentId = $jobComment->getId();

        self::assertSame($data['message'], $jobComment->getMessage());
        self::assertSame($data['userId'], $jobComment->getUser() ? $jobComment->getUser()->getId() : null);
        self::assertSame($data['jobId'], $jobComment->getJob() ? $jobComment->getJob()->getId() : null);
        self::assertSame($data['createdDate'], $jobComment->getCreatedDate()->format(\DateTime::ISO8601));

        $this->client->request('DELETE', sprintf('/api/v1.0/scheduling/jobs/comments/%d', $jobCommentId));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->em->clear();
        self::assertNull($this->em->find(JobComment::class, $jobCommentId));
    }
}
