<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Tests\Controller;

use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Nette\Utils\Json;
use Tests\Functional\AdminWebTestCase;

class TicketControllerTest extends AdminWebTestCase
{
    /**
     * @group TicketController
     */
    public function testClient(): void
    {
        $this->client->followRedirects(false);

        $option = $this->em->getRepository(Option::class)->findOneBy(
            [
                'code' => Option::SERVER_FQDN,
            ]
        );
        $option->setValue('localhost');
        $this->em->flush($option);
        $this->client->getContainer()->get(Options::class)->refresh();

        // test show client without tickets
        $this->client->request('GET', '/client/16/tickets');
        self::assertTrue($this->client->getResponse()->isSuccessful());

        // test show tickets
        $this->client->request('GET', '/client/21/tickets');
        self::assertTrue($this->client->getResponse()->isSuccessful());

        // test new ticket
        $crawler = $this->client->request('GET', '/client/16/ticket-new');
        self::assertTrue($this->client->getResponse()->isSuccessful());
        $form = $crawler->filter('#add-new-ticket-form')->form();

        $this->client->submit(
            $form,
            [
                'ticket_user[subject]' => 'TicketControllerTest::testNewTicketAction__subject',
                'ticket_user[message]' => 'TicketControllerTest::testNewTicketAction__message',
            ]
        );

        self::assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );

        $data = Json::decode($this->client->getResponse()->getContent(), Json::FORCE_ARRAY);
        self::assertTrue(isset($data['redirect']), $this->client->getResponse()->getContent());
    }
}
