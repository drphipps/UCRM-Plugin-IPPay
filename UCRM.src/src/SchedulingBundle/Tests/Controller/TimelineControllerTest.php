<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Tests\Controller;

use SchedulingBundle\Entity\Job;
use Tests\Functional\AdminWebTestCase;

class TimelineControllerTest extends AdminWebTestCase
{
    /**
     * @group TimelineController
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function urlProvider(): array
    {
        return [
            ['/scheduling/timeline'],
            ['/scheduling/timeline/my'],
        ];
    }

    /**
     * @group TimelineController
     */
    public function testTimelineEdit(): void
    {
        $this->client->followRedirects(false);

        // create a job
        $crawler = $this->client->request('GET', '/scheduling/job/new');
        self::assertTrue($this->client->getResponse()->isSuccessful());
        $form = $crawler->filter('#job-form')->form();

        $this->client->submit(
            $form,
            [
                'job[title]' => 'testTimelineEdit',
            ]
        );

        self::assertTrue($this->client->getResponse()->isRedirect());

        $job = $this->em->getRepository(Job::class)->findOneBy(
            [
                'title' => 'testTimelineEdit',
            ]
        );
        self::assertNotNull($job);

        // test timeline edit
        $this->client->request(
            'POST',
            sprintf('/scheduling/timeline/%d/edit', $job->getId()),
            [
                'item' => [
                    'id' => $job->getId(),
                    'start' => '2017-05-11T14:00:00+02:00',
                    'end' => '2017-05-11T16:00:00+02:00',
                    'group' => 2,
                ],
            ]
        );
        self::assertTrue($this->client->getResponse()->isSuccessful());
        $this->em->refresh($job);
        self::assertNotNull($job->getAssignedUser());
        self::assertSame(2, $job->getAssignedUser()->getId());
        self::assertNotNull($job->getDate());
        self::assertSame(120, $job->getDuration());
    }
}
