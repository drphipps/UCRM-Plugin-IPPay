<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Client;

use AppBundle\Entity\Client;
use AppBundle\Event\Client\ClientInvitationEmailSentEvent;
use AppBundle\Facade\ClientFacade;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ClientInvitationEmailSentSubscriber implements EventSubscriberInterface
{
    /**
     * @var ClientFacade
     */
    private $clientFacade;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(ClientFacade $clientFacade, EntityManager $em)
    {
        $this->clientFacade = $clientFacade;
        $this->em = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ClientInvitationEmailSentEvent::class => 'handleClientInvitationEmailSentEvent',
        ];
    }

    public function handleClientInvitationEmailSentEvent(ClientInvitationEmailSentEvent $event): void
    {
        $client = $this->em->find(Client::class, $event->getClientId());
        if ($client) {
            $this->clientFacade->handleInvitationEmailSent($client);
        }
    }
}
