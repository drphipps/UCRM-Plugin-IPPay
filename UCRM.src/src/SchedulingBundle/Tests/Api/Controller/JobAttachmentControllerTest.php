<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Tests\Api\Controller;

use Nette\Utils\Json;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobAttachment;
use Tests\Functional\ApiWebTestCase;

class JobAttachmentControllerTest extends ApiWebTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $job = new Job();
        $job->setTitle('JobAttachmentControllerTest');
        $this->em->persist($job);

        $jobAttachment = new JobAttachment();
        $jobAttachment->setJob($job);
        $jobAttachment->setFilename('JobAttachmentControllerTest1234567890.png');
        $jobAttachment->setOriginalFilename('JobAttachmentControllerTest.png');
        $jobAttachment->setSize(2000);
        $jobAttachment->setMimeType('image/png');

        $this->em->persist($jobAttachment);

        $this->em->flush();
    }

    /**
     * @group ApiJobAttachmentController
     */
    public function testJobAttachmentsGet(): void
    {
        $jobAttachmentId = $this->getMaxId(JobAttachment::class);

        $this->client->request(
            'GET',
            sprintf('/api/v1.0/scheduling/jobs/attachments/%d', $jobAttachmentId)
        );

        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertTrue(
            $this->client->getResponse()->headers->contains('Content-Type', 'application/json')
        );

        $data = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);

        self::assertArrayHasKey('id', $data);
        self::assertSame($jobAttachmentId, $data['id']);
    }

    /**
     * @group ApiJobAttachmentController
     */
    public function testJobAttachmentsCollectionGet(): void
    {
        $this->client->request('GET', '/api/v1.0/scheduling/jobs/attachments');

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
     * @group ApiJobAttachmentController
     */
    public function testJobAttachmentPatch()
    {
        $jobId = $this->getMaxId(Job::class);
        $jobAttachmentId = $this->getMaxId(JobAttachment::class);
        $data = [
            'filename' => 'newAttachmentName.png',
            'jobId' => $jobId,
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/v1.0/scheduling/jobs/attachments/%d', $jobAttachmentId),
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
        self::assertSame($jobAttachmentId, $patchedData['id']);
        self::assertSame($data['filename'], $patchedData['filename']);
        self::assertSame($data['jobId'], $patchedData['jobId']);
    }

    /**
     * @group ApiJobAttachmentController
     */
    public function testJobAttachmentPostAndDelete()
    {
        $jobId = $this->getMaxId(Job::class);
        $data = [
            'filename' => 'blue.gif',
            'jobId' => $jobId,
            'file' => 'R0lGODdhCgAKAPAAAAAk/yZFySH5BAEAAAEALAAAAAAKAAoAAAIIhI+py+0PYysAOw==',
        ];

        $this->client->request(
            'POST',
            '/api/v1.0/scheduling/jobs/attachments',
            [],
            [],
            [],
            Json::encode($data)
        );

        $id = $this->validatePostResponseAndGetId(
            $this->client->getResponse(),
            '~/api/v1.0/scheduling/jobs/attachments/(\d+)$~'
        );
        $jobAttachment = $this->em->find(
            JobAttachment::class,
            $id
        );

        self::assertNotNull($jobAttachment);

        if ($jobAttachment) {
            $jobAttachmentId = $jobAttachment->getId();
            self::assertSame($data['filename'], $jobAttachment->getOriginalFilename());
            self::assertSame($data['jobId'], $jobAttachment->getJob()->getId());

            $this->client->request('DELETE', sprintf('/api/v1.0/scheduling/jobs/attachments/%d', $jobAttachmentId));
            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            $this->em->clear();
            self::assertNull($this->em->find(JobAttachment::class, $jobAttachmentId));
        }
    }

    /**
     * @group ApiJobAttachmentController
     */
    public function testJobAttachmentPostAndGetFile()
    {
        $jobId = $this->getMaxId(Job::class);
        $data = [
            'filename' => 'blue.gif',
            'jobId' => $jobId,
            'file' => 'R0lGODdhCgAKAPAAAAAk/yZFySH5BAEAAAEALAAAAAAKAAoAAAIIhI+py+0PYysAOw==',
        ];

        $this->client->request(
            'POST',
            '/api/v1.0/scheduling/jobs/attachments',
            [],
            [],
            [],
            Json::encode($data)
        );

        $id = $this->validatePostResponseAndGetId(
            $this->client->getResponse(),
            '~/api/v1.0/scheduling/jobs/attachments/(\d+)$~'
        );
        $jobAttachment = $this->em->find(
            JobAttachment::class,
            $id
        );

        self::assertNotNull($jobAttachment);

        if ($jobAttachment) {
            $jobAttachmentId = $jobAttachment->getId();
            self::assertSame($data['filename'], $jobAttachment->getOriginalFilename());
            self::assertSame($data['jobId'], $jobAttachment->getJob()->getId());

            $this->client->request('GET', sprintf('/api/v1.0/scheduling/jobs/attachments/%d/file', $jobAttachmentId));

            self::assertSame(200, $this->client->getResponse()->getStatusCode());
        }
    }
}
