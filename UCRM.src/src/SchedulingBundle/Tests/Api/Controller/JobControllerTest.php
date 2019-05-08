<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Tests\Api\Controller;

use Nette\Utils\Json;
use SchedulingBundle\Entity\Job;
use Tests\Functional\ApiWebTestCase;

class JobControllerTest extends ApiWebTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $job = new Job();
        $job->setTitle('JobControllerTest');
        $this->em->persist($job);
        $this->em->flush();
    }

    /**
     * @group ApiJobController
     */
    public function testJobGet(): void
    {
        $jobId = $this->getMaxId(Job::class);

        $this->client->request(
            'GET',
            sprintf('/api/v1.0/scheduling/jobs/%d', $jobId)
        );

        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertTrue(
            $this->client->getResponse()->headers->contains('Content-Type', 'application/json')
        );

        $data = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);

        self::assertArrayHasKey('id', $data);
        self::assertSame($jobId, $data['id']);
    }

    /**
     * @group ApiJobController
     */
    public function testJobsCollectionGet(): void
    {
        $this->client->request('GET', '/api/v1.0/scheduling/jobs');

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
     * @group ApiJobController
     */
    public function testJobPatch()
    {
        $jobId = $this->getMaxId(Job::class);
        $data = [
            'title' => 'title',
            'description' => 'description',
            'assignedUserId' => 1,
            'clientId' => 1,
            'date' => '2017-05-17T11:00:00+0200',
            'duration' => 97,
            'status' => 1,
            'address' => 'Prague, Czech republic',
            'gpsLat' => '',
            'gpsLon' => '',
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/v1.0/scheduling/jobs/%d', $jobId),
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
        self::assertSame($jobId, $patchedData['id']);
        self::assertSame($data['title'], $patchedData['title']);
        self::assertSame($data['description'], $patchedData['description']);
        self::assertSame($data['assignedUserId'], $patchedData['assignedUserId']);
        self::assertSame($data['clientId'], $patchedData['clientId']);
        self::assertSame($data['date'], $patchedData['date']);
        self::assertSame($data['duration'], $patchedData['duration']);
        self::assertSame($data['status'], $patchedData['status']);
        self::assertSame($data['address'], $patchedData['address']);
        self::assertSame(null, $patchedData['gpsLat']);
        self::assertSame(null, $patchedData['gpsLon']);
    }

    /**
     * @group ApiJobController
     */
    public function testJobPostAndDelete()
    {
        $data = [
            'title' => 'testJobPost',
            'description' => 'description',
            'assignedUserId' => 1,
            'clientId' => 1,
            'date' => '2017-05-17T11:00:00+0200',
            'duration' => 97,
            'status' => 1,
            'address' => 'Prague, Czech republic',
            'gpsLat' => '',
            'gpsLon' => '',
        ];

        $this->client->request(
            'POST',
            '/api/v1.0/scheduling/jobs',
            [],
            [],
            [],
            Json::encode($data)
        );

        $id = $this->validatePostResponseAndGetId(
            $this->client->getResponse(),
            '~/api/v1.0/scheduling/jobs/(\d+)$~'
        );
        $job = $this->em->find(
            Job::class,
            $id
        );

        self::assertSame($data['title'], $job->getTitle());
        self::assertSame($data['description'], $job->getDescription());
        self::assertSame($data['assignedUserId'], $job->getAssignedUser() ? $job->getAssignedUser()->getId() : null);
        self::assertSame($data['clientId'], $job->getClient() ? $job->getClient()->getId() : null);
        self::assertSame($data['date'], $job->getDate()->format(\DateTime::ISO8601));
        self::assertSame($data['duration'], $job->getDuration());
        self::assertSame($data['status'], $job->getStatus());
        self::assertSame($data['address'], $job->getAddress());
        self::assertSame(null, $job->getGpsLat());
        self::assertSame(null, $job->getGpsLon());

        $this->client->request('DELETE', sprintf('/api/v1.0/scheduling/jobs/%d', $job->getId()));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->em->clear();
        self::assertNull($this->em->find(Job::class, $job->getId()));
    }
}
