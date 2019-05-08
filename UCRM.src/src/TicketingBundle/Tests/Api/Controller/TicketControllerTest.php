<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Tests\Api\Controller;

use AppBundle\Entity\Client;
use Nette\Utils\Json;
use Tests\Functional\ApiWebTestCase;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;

class TicketControllerTest extends ApiWebTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $ticket = new Ticket();
        $ticket->setSubject('TicketControllerTest');
        $this->em->persist($ticket);

        $ticketComment = new TicketComment();
        $ticketComment->setTicket($ticket);
        $ticketComment->setBody('Some Ticket comment');

        $this->em->persist($ticketComment);

        $this->em->flush();
    }

    /**
     * @group ApiTicketController
     */
    public function testTicketGet(): void
    {
        $ticket = $this->getMaxId(Ticket::class);

        $this->client->request(
            'GET',
            sprintf('/api/v1.0/ticketing/tickets/%d', $ticket)
        );

        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertTrue(
            $this->client->getResponse()->headers->contains('Content-Type', 'application/json')
        );

        $data = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);

        self::assertArrayHasKey('id', $data);
        self::assertSame($ticket, $data['id']);
    }

    /**
     * @group ApiTicketController
     */
    public function testTicketCollectionGet()
    {
        $this->client->request('GET', '/api/v1.0/ticketing/tickets');

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
     * @group ApiTicketController
     */
    public function testTicketPatch()
    {
        $ticketId = $this->getMaxId(Ticket::class);
        $data = [
            'subject' => 'TicketControllerTestNew',
            'clientId' => $this->getMaxId(Client::class),
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/v1.0/ticketing/tickets/%d', $ticketId),
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
        self::assertSame($ticketId, $patchedData['id']);
        self::assertSame($data['subject'], $patchedData['subject']);
        self::assertSame($data['clientId'], $patchedData['clientId']);
    }

    /**
     * @group ApiTicketController
     */
    public function testTicketPostAndDelete()
    {
        $data = [
            'subject' => 'TicketControllerTest',
            'clientId' => $this->getMaxId(Client::class),
            'public' => false,
            'activity' => [
                [
                    'userId' => null,
                    'public' => false,
                    'createdAt' => '2017-05-23T09:20:13+0000',
                    'comment' => [
                        'body' => '',
                    ],
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/api/v1.0/ticketing/tickets',
            [],
            [],
            [],
            Json::encode($data)
        );

        $id = $this->validatePostResponseAndGetId(
            $this->client->getResponse(),
            '~/api/v1.0/ticketing/tickets/(\d+)$~'
        );
        $ticket = $this->em->find(
            Ticket::class,
            $id
        );

        self::assertSame($data['subject'], $ticket->getSubject());

        $this->client->request('DELETE', sprintf('/api/v1.0/ticketing/tickets/%d', $ticket->getId()));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->em->clear();
        self::assertNull($this->em->find(Ticket::class, $ticket->getId()));
    }
}
