<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Email;

use AppBundle\Facade\EmailLogFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ResendEmailsConsumer extends AbstractConsumer
{
    /**
     * @var EmailLogFacade
     */
    private $emailLogFacade;

    public function __construct(
        EmailLogFacade $emailLogFacade,
        EntityManagerInterface $em,
        Options $options,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger, $options);

        $this->emailLogFacade = $emailLogFacade;
    }

    protected function getMessageClass(): string
    {
        return ResendEmailsMessage::class;
    }

    public function executeBody(array $data): int
    {
        $resent = $this->emailLogFacade->resendFailedEmails($data['emailLogIds']);

        if ($resent['success'] > 0) {
            $this->logger->info(sprintf('%d emails have been added to the send queue.', $resent['success']));
        }

        if ($resent['fail'] > 0) {
            $this->logger->error(sprintf('Adding of %d emails to the send queue failed.', $resent['fail']));
        }

        return self::MSG_ACK;
    }
}
