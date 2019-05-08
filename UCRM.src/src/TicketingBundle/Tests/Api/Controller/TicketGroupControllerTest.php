<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Tests\Api\Controller;

use Nette\Utils\Json;
use Tests\Functional\ApiWebTestCase;
use TicketingBundle\Entity\TicketGroup;

class TicketGroupControllerTest extends ApiWebTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $ticketGroup = new TicketGroup();
        $ticketGroup->setName('TicketGroupControllerTest');
        $this->em->persist($ticketGroup);
        $this->em->flush();
    }

    /**
     * @group ApiTicketGroupController
     */
    public function testTicketGroupGet(): void
    {
        $ticketGroup = $this->getMaxId(TicketGroup::class);

        $this->client->request(
            'GET',
            sprintf('/api/v1.0/ticketing/ticket-groups/%d', $ticketGroup)
        );

        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertTrue(
            $this->client->getResponse()->headers->contains('Content-Type', 'application/json')
        );

        $data = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);

        self::assertArrayHasKey('id', $data);
        self::assertSame($ticketGroup, $data['id']);
    }

    /**
     * @group ApiTicketGroupController
     */
    public function testTicketGroupCollectionGet()
    {
        $this->client->request('GET', '/api/v1.0/ticketing/ticket-groups');

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
     * @group ApiTicketGroupController
     */
    public function testTicketGroupPatch()
    {
        $ticketGroupId = $this->getMaxId(TicketGroup::class);
        $data = [
            'name' => 'NewName',
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/v1.0/ticketing/ticket-groups/%d', $ticketGroupId),
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
        self::assertSame($ticketGroupId, $patchedData['id']);
        self::assertSame($data['name'], $patchedData['name']);
    }

    /**
     * @group ApiTicketGroupController
     */
    public function testTicketGroupPostAndDelete()
    {
        $data = [
            'name' => 'Accountants',
        ];

        $this->client->request(
            'POST',
            '/api/v1.0/ticketing/ticket-groups',
            [],
            [],
            [],
            Json::encode($data)
        );

        $id = $this->validatePostResponseAndGetId(
            $this->client->getResponse(),
            '~/api/v1.0/ticketing/ticket-groups/(\d+)$~'
        );
        $ticketGroup = $this->em->find(
            TicketGroup::class,
            $id
        );

        self::assertSame($data['name'], $ticketGroup->getName());

        $this->client->request('DELETE', sprintf('/api/v1.0/ticketing/ticket-groups/%d', $ticketGroup->getId()));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->em->clear();
        self::assertNull($this->em->find(TicketGroup::class, $ticketGroup->getId()));
    }
}
