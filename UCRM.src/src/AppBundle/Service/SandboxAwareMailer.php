<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Service;

use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Swift_Mime_Message;

class SandboxAwareMailer extends \Swift_Mailer
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Options
     */
    private $options;

    public function __construct(\Swift_Transport $transport, EntityManagerInterface $entityManager, Options $options)
    {
        $this->entityManager = $entityManager;
        $this->options = $options;

        parent::__construct($transport);
    }

    public function send(Swift_Mime_Message $message, &$failedRecipients = null): ?int
    {
        if ((bool) $this->options->getGeneral(General::SANDBOX_MODE)) {
            if (! $this->getGlobalEmail()) {
                return 0;
            }

            $message->setTo($this->getGlobalEmail());
            $message->setCc([]);
            $message->setBcc([]);
        }

        $sender = $this->options->get(Option::MAILER_SENDER_ADDRESS);
        if ($sender && ! $message->getSender()) {
            $message->setSender($sender);
        }

        return parent::send($message, $failedRecipients);
    }

    private function getGlobalEmail(): ?string
    {
        $superAdmin = $this->entityManager->getRepository(User::class)->findOneBy(
            [
                'deletedAt' => null,
                'role' => User::ROLE_SUPER_ADMIN,
            ]
        );

        return $superAdmin ? $superAdmin->getEmail() : null;
    }
}
