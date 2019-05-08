<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Handler;

use AppBundle\DataProvider\OrganizationDataProvider;
use AppBundle\Entity\Option;
use AppBundle\Exception\ImapConnectionException;
use AppBundle\Factory\LockSemaphoreFactory;
use AppBundle\Service\ExceptionTracker;
use AppBundle\Service\Options;
use Ddeboer\Imap\Exception\ImapGetmailboxesException;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;
use Ddeboer\Imap\MessageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketCommentMailRemoved;
use TicketingBundle\Entity\TicketImapEmailBlacklist;
use TicketingBundle\Entity\TicketImapInbox;
use TicketingBundle\Service\Facade\TicketImapInboxFacade;
use TicketingBundle\Service\Facade\TicketMailFacade;
use TicketingBundle\Service\Factory\TicketImapModelFactory;

class TicketImapImportHandler
{
    public const X_MAILER_HEADER = 'UCRM-Ticketing';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var OrganizationDataProvider
     */
    private $organizationDataProvider;

    /**
     * @var TicketMailFacade
     */
    private $ticketMailFacade;

    /**
     * @var TicketImapModelFactory
     */
    private $ticketImapModelFactory;

    /**
     * @var TicketImapInboxFacade
     */
    private $ticketImapInboxFacade;

    /**
     * @var LockSemaphoreFactory
     */
    private $lockSemaphoreFactory;

    /**
     * @var ExceptionTracker
     */
    private $exceptionTracker;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Options $options,
        OrganizationDataProvider $organizationDataProvider,
        TicketMailFacade $ticketMailFacade,
        TicketImapModelFactory $ticketImapModelFactory,
        TicketImapInboxFacade $ticketImapInboxFacade,
        LockSemaphoreFactory $lockSemaphoreFactory,
        ExceptionTracker $exceptionTracker
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->options = $options;
        $this->organizationDataProvider = $organizationDataProvider;
        $this->ticketMailFacade = $ticketMailFacade;
        $this->ticketImapModelFactory = $ticketImapModelFactory;
        $this->ticketImapInboxFacade = $ticketImapInboxFacade;
        $this->lockSemaphoreFactory = $lockSemaphoreFactory;
        $this->exceptionTracker = $exceptionTracker;
    }

    /**
     * @throws ImapConnectionException
     */
    public function import(): void
    {
        if (! $this->options->get(Option::TICKETING_ENABLED)) {
            $this->logger->notice('Ticketing is disabled. Import is stopped.');

            return;
        }

        $dateTimeUtc = new \DateTime('now', new \DateTimeZone('UTC'));

        $inboxes = $this->entityManager->getRepository(TicketImapInbox::class)->findBy(
            [
                'enabled' => true,
            ]
        );
        foreach ($inboxes as $inbox) {
            $lockResource = sprintf('%s ImapInbox %s', __CLASS__, $inbox->getId());
            $lock = $this->lockSemaphoreFactory->create($lockResource);
            if ($lock->acquire()) {
                try {
                    $ticketImapModel = $this->ticketImapModelFactory->create($inbox);
                } catch (\Throwable $e) {
                    $this->logger->error($e->getMessage());
                    $lock->release();
                    continue;
                }

                try {
                    if ($inbox->getImportStartDate()) {
                        $date = \DateTimeImmutable::createFromMutable($inbox->getImportStartDate());
                    } else {
                        // Email not yet imported
                        $date = $this->ticketImapInboxFacade->getDefaultImportStartDate();
                    }

                    foreach ($ticketImapModel->getMessagesSince($date) as $newMessage) {
                        if (
                            $this->isValidMessage($newMessage)
                            && ! $this->shouldIgnoreMessage($newMessage, $inbox)
                        ) {
                            $this->ticketMailFacade->handleNewEmail($newMessage, $inbox);
                            $newMessage->markAsSeen();
                        }

                        // 'udate' from message headers is timestamp when message is received
                        $udate = $newMessage->getHeaders()->get('udate')
                            ? new \DateTime(sprintf('@%s', $newMessage->getHeaders()->get('udate')))
                            : $dateTimeUtc;

                        // Prevent set TicketImapInbox to future in case that $udate is in future.
                        $inbox->setImportStartDate($udate > $dateTimeUtc ? $dateTimeUtc : $udate);
                        $this->ticketImapInboxFacade->handleUpdate($inbox);
                    }
                } catch (MailboxDoesNotExistException | ImapGetmailboxesException $exception) {
                    // Silently ignore
                    $this->logger->error($exception->getMessage());
                } catch (\Throwable $e) {
                    $this->exceptionTracker->captureException($e);
                    $this->logger->error($e->getMessage());
                } finally {
                    $lock->release();
                }
            } else {
                $this->logger->info(sprintf('Import from inbox is locked (%s)', $lockResource));
            }
        }
    }

    private function shouldIgnoreMessage(MessageInterface $message, TicketImapInbox $imapInbox): bool
    {
        $fromAddress = $message->getFrom()->getAddress();

        if (stripos($message->getRawHeaders(), 'X-Mailer: ' . self::X_MAILER_HEADER) !== false) {
            $this->logger->info(
                sprintf(
                    'Message %s ignored because it\'s from a UCRM (contains X-Mailer: ' . self::X_MAILER_HEADER . ').',
                    $message->getNumber()
                )
            );

            return true;
        }

        $systemEmailAddresses = in_array(
            $fromAddress,
            array_merge(
                array_values($this->organizationDataProvider->getEmails()),
                array_filter([$this->options->get(Option::SUPPORT_EMAIL_ADDRESS)])
            ),
            true
        );
        if ($systemEmailAddresses) {
            $this->logger->info(
                sprintf(
                    'Message %s from %s is ignored because it\'s from a UCRM system email or email blacklist.',
                    $message->getNumber(),
                    $message->getFrom()->getAddress()
                )
            );

            return true;
        }

        $emailBlacklists = $this->entityManager->getRepository(TicketImapEmailBlacklist::class)
            ->findBy(['emailAddress' => $fromAddress]);
        if ($emailBlacklists) {
            $this->logger->info(
                sprintf(
                    'Message %s ignored because it\'s from a UCRM blacklisted email.',
                    $message->getNumber()
                )
            );

            return true;
        }

        $alreadyImported = $this->entityManager->getRepository(TicketComment::class)
            ->findBy(
                [
                    'emailId' => trim($message->getId(), '<>'),
                    'emailFromAddress' => $message->getFrom()->getAddress(),
                    'emailDate' => $message->getDate()->setTimezone(new \DateTimeZone('UTC')),
                ]
            );

        if ($alreadyImported) {
            $this->logger->info(
                sprintf(
                    'Message %s ignored because it\'s already imported.',
                    $message->getNumber()
                )
            );

            return true;
        }

        $alreadyImportedAndRemoved = $this->entityManager->getRepository(TicketCommentMailRemoved::class)
            ->findBy(
                [
                    'emailId' => trim($message->getId() ?? '', '<>'),
                    'inbox' => $imapInbox,
                ]
            );
        if ($alreadyImportedAndRemoved) {
            $this->logger->info(
                sprintf(
                    'Message %s ignored because it\'s already imported and removed.',
                    $message->getNumber()
                )
            );

            return true;
        }

        return false;
    }

    private function isValidMessage(MessageInterface $message): bool
    {
        if ($message->getFrom() && $message->getId() && $message->getDate()) {
            return true;
        }

        $this->logger->info(
            sprintf(
                'Message %s ignored because it\'s not valid.',
                $message->getNumber()
            )
        );

        return false;
    }
}
