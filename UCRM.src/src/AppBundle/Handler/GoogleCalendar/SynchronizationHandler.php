<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Handler\GoogleCalendar;

use AppBundle\DataProvider\GoogleCalendarDataProvider;
use AppBundle\Entity\User;
use AppBundle\Event\GoogleCalendar\SynchronizationErrorEvent;
use AppBundle\Exception\GoogleCalendarException;
use AppBundle\Exception\OAuthException;
use AppBundle\Facade\GoogleOAuthFacade;
use AppBundle\Facade\UserFacade;
use AppBundle\Factory\GoogleCalendar\SynchronizerFactory;
use AppBundle\Service\GoogleCalendar\ClientFactory;
use AppBundle\Service\GoogleCalendar\Operation;
use Psr\Log\LoggerInterface;
use SchedulingBundle\DataProvider\JobDataProvider;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Request\JobCollectionRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SynchronizationHandler
{
    private const SYNCHRONIZATION_PAUSE = '+24 hours';
    private const EVENT_LIMIT_MIN = '-1 month';
    private const EVENT_LIMIT_MAX = '+3 months';

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var GoogleCalendarDataProvider
     */
    private $googleCalendarDataProvider;

    /**
     * @var GoogleOAuthFacade
     */
    private $googleOAuthFacade;

    /**
     * @var UserFacade
     */
    private $userFacade;

    /**
     * @var JobDataProvider
     */
    private $jobDataProvider;

    /**
     * @var SynchronizerFactory
     */
    private $synchronizerFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        ClientFactory $clientFactory,
        GoogleCalendarDataProvider $googleCalendarDataProvider,
        GoogleOAuthFacade $googleOAuthFacade,
        UserFacade $userFacade,
        JobDataProvider $jobDataProvider,
        SynchronizerFactory $synchronizerFactory,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->clientFactory = $clientFactory;
        $this->googleCalendarDataProvider = $googleCalendarDataProvider;
        $this->googleOAuthFacade = $googleOAuthFacade;
        $this->userFacade = $userFacade;
        $this->jobDataProvider = $jobDataProvider;
        $this->synchronizerFactory = $synchronizerFactory;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function synchronizeUser(User $user): void
    {
        try {
            $calendarId = $user->getGoogleCalendarId();
            $this->googleOAuthFacade->refreshTokenIfExpired($user);
            $client = $this->clientFactory->create($user->getGoogleOAuthToken());

            $startDate = new \DateTimeImmutable(self::EVENT_LIMIT_MIN);
            $endDate = new \DateTimeImmutable(self::EVENT_LIMIT_MAX);
            $events = $this->googleCalendarDataProvider->getAllManagedEvents(
                $client,
                $calendarId,
                $startDate,
                $endDate
            );

            $jobCollectionRequest = new JobCollectionRequest();
            $jobCollectionRequest->user = $user;
            $jobCollectionRequest->startDate = $startDate;
            $jobCollectionRequest->endDate = $endDate;

            $jobs = $this->jobDataProvider->getAllJobs($jobCollectionRequest);
            $synchronizer = $this->synchronizerFactory->create($client, $calendarId);

            $operations = $this->getOperations($events, $jobs);
            foreach ($operations as $operation) {
                $synchronizer->processOperation($operation);
            }

            $user->setNextGoogleCalendarSynchronization(new \DateTime(self::SYNCHRONIZATION_PAUSE));
            $user->setGoogleSynchronizationErrorNotificationSent(false);
            $this->userFacade->handleUpdate($user);
            $this->logger->info(
                sprintf(
                    'Synchronized user ID %d, next synchronization planned for %s.',
                    $user->getId(),
                    $user->getNextGoogleCalendarSynchronization()->format(\DateTime::RFC3339)
                )
            );
        } catch (OAuthException | GoogleCalendarException $exception) {
            $this->eventDispatcher->dispatch(
                SynchronizationErrorEvent::class,
                new SynchronizationErrorEvent($user, $exception)
            );
            $user->setGoogleSynchronizationErrorNotificationSent(true);
            $this->userFacade->handleUpdate($user);
            $this->logger->warning($exception->getMessage());
        }
    }

    private function getOperations(
        array $events,
        array $jobs
    ): \Generator {
        $events = $this->prepareEvents($events);
        $jobs = $this->prepareJobs($jobs);

        foreach ($jobs as $uuid => $job) {
            if (array_key_exists($uuid, $events)) {
                // If the event exists in both UCRM and Google Calendar, update it.
                yield new Operation(Operation::TYPE_UPDATE, $job, $events[$uuid]);
            } else {
                // If the event exists only in UCRM, import it.
                yield new Operation(Operation::TYPE_IMPORT, $job, null);
            }

            unset($events[$uuid]);
        }

        // All events not updated, or imported have to be deleted if not already done.
        foreach ($events as $uuid => $event) {
            if ($event->getStatus() !== Operation::EVENT_STATUS_CANCELLED) {
                yield new Operation(Operation::TYPE_DELETE, null, $event);
            }
        }
    }

    /**
     * @param array|\Google_Service_Calendar_Event[] $events
     *
     * @return array|\Google_Service_Calendar_Event[]
     */
    private function prepareEvents(array $events): array
    {
        $preparedEvents = [];
        foreach ($events as $event) {
            $preparedEvents[$event->getICalUID()] = $event;
        }

        return $preparedEvents;
    }

    /**
     * @param array|Job[] $jobs
     *
     * @return array|Job[]
     */
    private function prepareJobs(array $jobs): array
    {
        $preparedJobs = [];
        foreach ($jobs as $job) {
            $preparedJobs[Operation::UUID_PREFIX . $job->getUuid()] = $job;
        }

        return $preparedJobs;
    }
}
