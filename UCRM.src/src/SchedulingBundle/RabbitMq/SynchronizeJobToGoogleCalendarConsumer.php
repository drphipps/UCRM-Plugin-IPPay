<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\RabbitMq;

use AppBundle\DataProvider\GoogleCalendarDataProvider;
use AppBundle\Entity\User;
use AppBundle\Event\GoogleCalendar\SynchronizationErrorEvent;
use AppBundle\Exception\GoogleCalendarException;
use AppBundle\Exception\OAuthException;
use AppBundle\Facade\GoogleOAuthFacade;
use AppBundle\Facade\UserFacade;
use AppBundle\Factory\GoogleCalendar\SynchronizerFactory;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\GoogleCalendar\ClientFactory;
use AppBundle\Service\GoogleCalendar\Operation;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SchedulingBundle\Entity\Job;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SynchronizeJobToGoogleCalendarConsumer extends AbstractConsumer
{
    /**
     * @var GoogleOAuthFacade
     */
    private $googleOAuthFacade;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var GoogleCalendarDataProvider
     */
    private $googleCalendarDataProvider;

    /**
     * @var SynchronizerFactory
     */
    private $synchronizerFactory;

    /**
     * @var UserFacade
     */
    private $userFacade;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Options $options,
        GoogleOAuthFacade $googleOAuthFacade,
        ClientFactory $clientFactory,
        GoogleCalendarDataProvider $googleCalendarDataProvider,
        SynchronizerFactory $synchronizerFactory,
        UserFacade $userFacade,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($em, $logger, $options);

        $this->googleOAuthFacade = $googleOAuthFacade;
        $this->clientFactory = $clientFactory;
        $this->googleCalendarDataProvider = $googleCalendarDataProvider;
        $this->synchronizerFactory = $synchronizerFactory;
        $this->userFacade = $userFacade;
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function getMessageClass(): string
    {
        return SynchronizeJobToGoogleCalendarMessage::class;
    }

    public function executeBody(array $data): int
    {
        $user = $this->entityManager->find(User::class, $data['job']['user_id']);
        if (! $user) {
            $this->logger->warning('User not found.');

            return self::MSG_REJECT;
        }

        try {
            $calendarId = $user->getGoogleCalendarId();
            $this->googleOAuthFacade->refreshTokenIfExpired($user);
            $client = $this->clientFactory->create($user->getGoogleOAuthToken());
            $synchronizer = $this->synchronizerFactory->create($client, $calendarId);

            switch ($data['type']) {
                case SynchronizeJobToGoogleCalendarMessage::TYPE_CREATE:
                    $job = $this->entityManager->find(Job::class, $data['job']['id']);
                    if (! $job) {
                        $this->logger->warning('Job not found.');

                        return self::MSG_REJECT;
                    }

                    $this->logger->info(sprintf('Creating TYPE_IMPORT operation for job ID %d', $job->getId()));
                    $operation = new Operation(Operation::TYPE_IMPORT, $job, null);

                    break;
                case SynchronizeJobToGoogleCalendarMessage::TYPE_UPDATE:
                    $job = $this->entityManager->find(Job::class, $data['job']['id']);
                    if (! $job) {
                        $this->logger->warning('Job not found.');

                        return self::MSG_REJECT;
                    }

                    $event = $this->googleCalendarDataProvider->getEventByUuid($client, $calendarId, $data['job']['uuid']);
                    if ($event) {
                        $this->logger->info(
                            sprintf(
                                'Creating TYPE_UPDATE operation for job ID %d, event UUID %s',
                                $job->getId(),
                                $event->getICalUID()
                            )
                        );
                        $operation = new Operation(Operation::TYPE_UPDATE, $job, $event);
                    } else {
                        $this->logger->info(sprintf('Creating TYPE_IMPORT operation for job ID %d', $job->getId()));
                        $operation = new Operation(Operation::TYPE_IMPORT, $job, null);
                    }

                    break;
                case SynchronizeJobToGoogleCalendarMessage::TYPE_DELETE:
                    $event = $this->googleCalendarDataProvider->getEventByUuid($client, $calendarId, $data['job']['uuid']);
                    if (! $event) {
                        $this->logger->warning('Event not found.');

                        return self::MSG_REJECT;
                    }

                    $this->logger->info(sprintf('Creating TYPE_DELETE operation for event UUID %s', $event->getICalUID()));
                    $operation = new Operation(Operation::TYPE_DELETE, null, $event);

                    break;
                default:
                    $this->logger->warning(sprintf('Unsupported type "%s".', $data['type']));

                    return self::MSG_REJECT;
            }

            $this->logger->info('Processing sync operation.');
            $synchronizer->processOperation($operation);

            $user->setGoogleSynchronizationErrorNotificationSent(false);
            $this->userFacade->handleUpdate($user);
        } catch (OAuthException | GoogleCalendarException $exception) {
            $this->eventDispatcher->dispatch(
                SynchronizationErrorEvent::class,
                new SynchronizationErrorEvent($user, $exception)
            );
            $user->setGoogleSynchronizationErrorNotificationSent(true);
            $this->userFacade->handleUpdate($user);
            $this->logger->warning(sprintf('An error happened: "%s"', $exception->getMessage()));

            return self::MSG_REJECT;
        }

        $this->logger->info('Done.');

        return self::MSG_ACK;
    }
}
