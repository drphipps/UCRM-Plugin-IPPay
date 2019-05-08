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

class TicketAttachmentControllerTest extends ApiWebTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $ticket = new Ticket();
        $ticket->setSubject('TicketAttachmentControllerTest');
        $this->em->persist($ticket);

        $ticketComment = new TicketComment();
        $ticketComment->setTicket($ticket);
        $ticketComment->setBody('Some Ticket comment');

        $this->em->persist($ticketComment);

        $this->em->flush();
    }

    /**
     * @group ApiTicketCommentAttachmentController
     */
    public function testTicketCommentAttachmentPostAndGetFile()
    {
        $ticketId = $this->getMaxId(Ticket::class);
        $userId = 1;

        $data = [
            'ticketId' => $ticketId,
            'userId' => $userId,
            'body' => 'Some Ticket comment with attachment',
            'public' => true,
            'attachments' => [
                [
                    'filename' => 'blueRectangle.gif',
                    'file' => 'R0lGODdhCgAKAPAAAAAk/yZFySH5BAEAAAEALAAAAAAKAAoAAAIIhI+py+0PYysAOw==',
                ],
            ],
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

        self::assertNotNull($ticketComment);

        if ($ticketComment) {
            self::assertSame($data['attachments'][0]['filename'], $ticketComment->getAttachments()->current()->getOriginalFilename());

            $this->client->request(
                'GET',
                sprintf(
                    '/api/v1.0/ticketing/tickets/comments/attachments/%d/file',
                    $ticketComment->getAttachments()->current()->getId()
                )
            );

            self::assertSame(200, $this->client->getResponse()->getStatusCode());
        }
    }
}
