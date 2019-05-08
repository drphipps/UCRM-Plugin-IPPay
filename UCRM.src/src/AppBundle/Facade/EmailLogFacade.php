<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\DataProvider\EmailLogDataProvider;
use AppBundle\Entity\EmailLog;
use AppBundle\Exception\EmailAttachmentNotFoundException;
use AppBundle\RabbitMq\Email\ResendEmailsMessage;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Util\Json;
use AppBundle\Util\Message;
use AppBundle\Util\Strings;
use Doctrine\ORM\EntityManager;
use RabbitMqBundle\RabbitMqEnqueuer;

class EmailLogFacade
{
    /**
     * @var EmailLogDataProvider
     */
    private $dataProvider;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EmailEnqueuer
     */
    private $emailEnqueuer;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var string
     */
    private $rootDir;

    public function __construct(
        EmailLogDataProvider $dataProvider,
        EntityManager $em,
        EmailEnqueuer $emailEnqueuer,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        string $rootDir
    ) {
        $this->dataProvider = $dataProvider;
        $this->em = $em;
        $this->emailEnqueuer = $emailEnqueuer;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->rootDir = $rootDir;
    }

    public function resendFailedEmailsSince(\DateTimeInterface $date): int
    {
        $ids = $this->dataProvider->getFailedEmailIdsSince($date);
        if ($ids) {
            $this->rabbitMqEnqueuer->enqueue(new ResendEmailsMessage($ids));
        }

        return count($ids);
    }

    /**
     * @throws EmailAttachmentNotFoundException
     */
    public function resendEmail(EmailLog $emailLog): ?EmailLog
    {
        $message = new Message();
        $message->setClient($emailLog->getClient());
        $message->setSubject($emailLog->getSubject());
        $message->setTo(explode(',', $emailLog->getRecipient()));
        $message->setFrom(Json::decodeJsonLeaveString($emailLog->getAddressFrom()));
        $message->setSender(Json::decodeJsonLeaveString($emailLog->getSender()));
        $message->setBody($emailLog->getBody(), 'text/html');
        $message->setMailing($emailLog->getBulkMail());

        if ($attachments = $emailLog->getAttachments()) {
            $attachments = explode(', ', $attachments);
            foreach ($attachments as $attachment) {
                // removeUpTraverseFromFilePath introduces at least some security to this,
                // but in the end we basically have to trust the saved attachment path
                $path = $this->rootDir . Strings::removeUpTraverseFromFilePath($attachment);

                if (file_exists($path)) {
                    $message->attach(\Swift_Attachment::fromPath($path));
                } else {
                    throw new EmailAttachmentNotFoundException('Attachment not found.');
                }
            }
        }

        $resentEmailLog = $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_HIGH);
        $emailLog->setStatus(EmailLog::STATUS_RESENT);
        $emailLog->setResentEmailLog($resentEmailLog);
        $resentEmailLog->setOriginalRecipient($emailLog->getOriginalRecipient());

        $this->em->flush();

        return $resentEmailLog;
    }

    public function handleEdit(EmailLog $emailLog): void
    {
        $this->em->flush();
    }

    public function resendFailedEmails(array $logIds): array
    {
        $resent = ['success' => 0, 'fail' => 0, 'failMessages' => []];
        foreach ($this->dataProvider->getFailedEmailsById($logIds) as $emailLog) {
            try {
                if ($this->resendEmail($emailLog)) {
                    ++$resent['success'];
                } else {
                    ++$resent['fail'];
                }
            } catch (\Exception $exception) {
                ++$resent['fail'];
                if (empty($resent['failMessages'][$exception->getMessage()])) {
                    $resent['failMessages'][$exception->getMessage()] = 0;
                }
                ++$resent['failMessages'][$exception->getMessage()];
            }
        }

        return $resent;
    }

    public function setDiscardedStatus(int $logId): int
    {
        $discarded = 0;
        $emailLog = $this->em->find(EmailLog::class, $logId);
        if ($emailLog->getStatus() === EmailLog::STATUS_ERROR && ! $emailLog->isDiscarded()) {
            $emailLog->setDiscarded(true);
            $discarded = 1;

            $this->em->flush();
        }

        return $discarded;
    }

    public function setAllDiscardedStatus(): int
    {
        $discarded = 0;
        $emailLogs = $this->em->getRepository(EmailLog::class)
            ->findBy(
                [
                    'status' => EmailLog::STATUS_ERROR,
                    'discarded' => false,
                ]
            );

        foreach ($emailLogs as $emailLog) {
            $emailLog->setDiscarded(true);
            ++$discarded;
        }

        if ($discarded) {
            $this->em->flush();
        }

        return $discarded;
    }
}
