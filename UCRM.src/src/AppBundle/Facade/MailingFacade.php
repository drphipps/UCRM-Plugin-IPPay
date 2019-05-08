<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\EmailLog;
use AppBundle\Entity\Mailing;
use AppBundle\Form\Data\MailingComposeMessageData;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Service\Mailing\MailingMessageComposer;
use Doctrine\ORM\EntityManager;

class MailingFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var MailingMessageComposer
     */
    private $mailingMessageComposer;

    /**
     * @var EmailEnqueuer
     */
    private $emailEnqueuer;

    /**
     * @var EmailLogger
     */
    private $emailLogger;

    public function __construct(
        EntityManager $em,
        MailingMessageComposer $mailingMessageComposer,
        EmailLogger $emailLogger,
        EmailEnqueuer $emailEnqueuer
    ) {
        $this->em = $em;
        $this->mailingMessageComposer = $mailingMessageComposer;
        $this->emailLogger = $emailLogger;
        $this->emailEnqueuer = $emailEnqueuer;
    }

    public function handleSendEmail(
        Mailing $mailing,
        array $filters,
        array $clients,
        MailingComposeMessageData $messageData
    ): void {
        $mailing->setCreatedDate(new \DateTime());
        $mailing->setMessage($messageData->body);
        $mailing->setSubject($messageData->subject);
        $mailing->setFilterOrganizations($filters['organization']);
        $mailing->setFilterClientType($filters['clientType']);
        $mailing->setFilterClientTag($filters['clientTag']);
        $mailing->setFilterServicePlan($filters['servicePlan']);
        $mailing->setFilterPeriodStartDay($filters['periodStartDay']);
        $mailing->setFilterSite($filters['site']);
        $mailing->setFilterDevice($filters['device']);

        $this->em->persist($mailing);
        $this->em->flush();

        foreach ($clients as $client) {
            $message = $this->mailingMessageComposer->composeMail($client, $mailing, $messageData->subject, $messageData->body);

            if (! $message->getTo()) {
                $this->emailLogger->log(
                    $message,
                    'Email could not be sent, because client %clientName% (ID: %clientId%) has no email set.',
                    EmailLog::STATUS_ERROR,
                    ['%clientName%' => $client->getNameForView(), '%clientId%' => $client->getId()]
                );
            } else {
                $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_MEDIUM);
            }
        }

        $this->em->flush();
    }
}
