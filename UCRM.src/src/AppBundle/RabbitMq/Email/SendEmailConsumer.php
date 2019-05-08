<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Email;

use AppBundle\RabbitMq\Exception\RejectStopConsumerException;
use AppBundle\Service\Email\EmailAntifloodException;
use AppBundle\Service\Email\EmailFilesystem;
use AppBundle\Service\Email\EmailFilesystemException;
use AppBundle\Service\Email\EmailLimiter;
use AppBundle\Service\Email\EmailLimitExceededException;
use AppBundle\Service\Options;
use AppBundle\Service\SandboxAwareMailer;
use AppBundle\Util\Sleeper;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This consumer intentionally does not extend AbstractConsumer, because it needs more customized error handling.
 */
class SendEmailConsumer implements ConsumerInterface
{
    private const SLEEP_TIME_ON_REQUEUE = 5;

    /**
     * @var \Swift_Mailer|SandboxAwareMailer
     */
    private $mailer;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var EmailFilesystem
     */
    private $emailFilesystem;

    /**
     * @var EmailLimiter
     */
    private $emailLimiter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Sleeper
     */
    private $sleeper;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        \Swift_Mailer $mailer,
        Options $options,
        EmailFilesystem $emailFilesystem,
        EmailLimiter $emailLimiter,
        LoggerInterface $logger,
        Sleeper $sleeper,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $entityManager
    ) {
        $this->mailer = $mailer;
        $this->options = $options;
        $this->emailFilesystem = $emailFilesystem;
        $this->emailLimiter = $emailLimiter;
        $this->logger = $logger;
        $this->sleeper = $sleeper;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(AMQPMessage $message): int
    {
        // This fixes rare problem with lots of messages consumed at once. No idea why it helps though. :)
        // The problem can be reproduced when generating invoices for 1000+ clients.
        // Random email logs were showing as still in queue even though they were correctly sent and consumed.
        usleep(100000);

        $this->entityManager->clear();
        $this->options->refresh();

        try {
            $data = Json::decode($message->getBody(), Json::FORCE_ARRAY);
        } catch (JsonException $exception) {
            $this->logger->warning(sprintf('Message is not valid JSON. Error: %s', $exception->getMessage()));

            return self::MSG_REJECT;
        }

        if (! isset($data['messageId'])) {
            $this->logger->warning('Message ID not set - invalid AMQPMessage.');

            return self::MSG_REJECT;
        }

        try {
            $email = $this->emailFilesystem->loadFromSpool($data['messageId']);
        } catch (EmailFilesystemException $exception) {
            $this->logger->warning($exception->getMessage());
            $this->emailFilesystem->removeFromSpool($data['messageId']);

            return self::MSG_REJECT;
        }

        try {
            $this->emailLimiter->checkLimits();
        } catch (EmailAntifloodException | EmailLimitExceededException $exception) {
            $this->logger->info(
                sprintf('Requeuing message "%s". Reason: "%s"', $data['messageId'], $exception->getMessage())
            );
            $this->sleeper->sleep(self::SLEEP_TIME_ON_REQUEUE);

            return self::MSG_REJECT_REQUEUE;
        }

        try {
            // Make sure any exception after the email is sent is caught to prevent double sending.
            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            $this->logger->warning(
                sprintf(
                    'Could not send message "%s". Error: %s',
                    $data['messageId'],
                    $exception->getMessage()
                )
            );

            throw new RejectStopConsumerException();
        } finally {
            try {
                $this->emailFilesystem->removeFromSpool($data['messageId']);
            } catch (\Throwable $exception) {
                $this->logger->warning($exception->getMessage());
            }

            try {
                $transport = $this->mailer->getTransport();
                if ($transport->isStarted()) {
                    $transport->stop();
                }
            } catch (\Throwable $exception) {
                $this->logger->warning($exception->getMessage());
            }
        }

        try {
            $this->emailLimiter->increaseCounters();
        } catch (\Throwable $exception) {
            $this->logger->warning($exception->getMessage());
        }

        if ($data['eventClass']) {
            $this->eventDispatcher->dispatch(
                $data['eventClass'],
                new $data['eventClass'](...$data['eventData'])
            );
        }

        return self::MSG_ACK;
    }
}
