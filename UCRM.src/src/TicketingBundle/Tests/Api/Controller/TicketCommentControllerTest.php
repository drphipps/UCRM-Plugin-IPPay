<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Tests\Api\Controller;

use Nette\Utils\Json;
use Tests\Functional\ApiWebTestCase;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;

class TicketCommentControllerTest extends ApiWebTestCase
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
     * @group ApiTicketCommentController
     */
    public function testTicketCommentGet(): void
    {
        $ticketCommentId = $this->getMaxId(TicketComment::class);

        $this->client->request(
            'GET',
            sprintf('/api/v1.0/ticketing/tickets/comments/%d', $ticketCommentId)
        );

        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertTrue(
            $this->client->getResponse()->headers->contains('Content-Type', 'application/json')
        );

        $data = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);

        self::assertArrayHasKey('id', $data);
        self::assertSame($ticketCommentId, $data['id']);
    }

    /**
     * @group ApiTicketCommentController
     */
    public function testTicketCommentCollectionGet()
    {
        $this->client->request('GET', '/api/v1.0/ticketing/tickets/comments');

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
     * @group ApiTicketCommentController
     */
    public function testTicketCommentPost()
    {
        $data = [
            'ticketId' => $this->getMaxId(Ticket::class),
            'userId' => 1,
            'public' => true,
            'body' => 'testTicketCommentPostAndDelete',
        ];

        $this->client->request(
            'POST',
            '/api/v1.0/ticketing/tickets/comments',
            [],
            [],
            [],
            Json::encode($data)
        );

        $id = $this->validatePostResponseAndGetId(
            $this->client->getResponse(),
            '~/api/v1.0/ticketing/tickets/comments/(\d+)$~'
        );
        $ticketComment = $this->em->find(
            TicketComment::class,
            $id
        );

        self::assertSame($data['body'], $ticketComment->getBody());
    }
}
