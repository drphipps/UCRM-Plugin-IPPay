<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\GoogleCalendar;

use AppBundle\Exception\GoogleCalendarException;
use AppBundle\Facade\GoogleCalendarFacade;
use AppBundle\Util\DateTimeFactory;
use Psr\Log\LoggerInterface;
use SchedulingBundle\Entity\Job;

class Synchronizer
{
    /**
     * @var GoogleCalendarFacade
     */
    private $googleCalendarFacade;

    /**
     * @var JobToEventConverter
     */
    private $jobToEventConverter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Google_Client
     */
    private $client;

    /**
     * @var string
     */
    private $calendarId;

    public function __construct(
        GoogleCalendarFacade $googleCalendarFacade,
        JobToEventConverter $jobToEventConverter,
        LoggerInterface $logger,
        \Google_Client $client,
        string $calendarId
    ) {
        $this->googleCalendarFacade = $googleCalendarFacade;
        $this->jobToEventConverter = $jobToEventConverter;
        $this->logger = $logger;
        $this->client = $client;
        $this->calendarId = $calendarId;
    }

    public function processOperation(Operation $operation): void
    {
        switch ($operation->getType()) {
            case Operation::TYPE_IMPORT:
                $this->processImport($operation->getJob());
                break;
            case Operation::TYPE_UPDATE:
                $this->processUpdate($operation->getJob(), $operation->getEvent());
                break;
            case Operation::TYPE_DELETE:
                $this->processDelete($operation->getEvent());
                break;
        }
    }

    private function processImport(Job $job): void
    {
        $event = $this->jobToEventConverter->convert($job);
        if (! $event) {
            return;
        }

        $this->logger->info(sprintf('Importing event ID "%s".', $event->getICalUID()));
        $this->googleCalendarFacade->importEvent($this->client, $this->calendarId, $event);
    }

    private function processUpdate(Job $job, \Google_Service_Calendar_Event $event): void
    {
        $localEvent = $this->jobToEventConverter->convert($job);
        if (! $localEvent || ! $this->isEventUpdateRequired($localEvent, $event)) {
            return;
        }

        if ($event->getStatus() === Operation::EVENT_STATUS_CANCELLED) {
            $event->setStatus(Operation::EVENT_STATUS_CONFIRMED);
        }
        $event = $this->jobToEventConverter->convert($job, $event);

        try {
            $this->logger->info(sprintf('Updating event ID "%s".', $event->getICalUID()));
            $this->googleCalendarFacade->updateEvent(
                $this->client,
                $this->calendarId,
                $event->getId(),
                $event
            );
        } catch (GoogleCalendarException $exception) {
            if ($exception->getCode() === 403) {
                $this->logger->warning(sprintf('Skipping event ID "%s".', $event->getICalUID()));
            } else {
                throw $exception;
            }
        }
    }

    private function processDelete(\Google_Service_Calendar_Event $event): void
    {
        $this->logger->info(sprintf('Deleting event ID %s', $event->getICalUID()));
        $this->googleCalendarFacade->deleteEvent($this->client, $this->calendarId, $event->getId());
    }

    private function isEventUpdateRequired(
        \Google_Service_Calendar_Event $ucrmEvent,
        \Google_Service_Calendar_Event $googleEvent
    ): bool {
        return $googleEvent->getStatus() === Operation::EVENT_STATUS_CANCELLED
            || ! $this->isSameDateTime($ucrmEvent->getStart()->getDateTime(), $googleEvent->getStart()->getDateTime())
            || ! $this->isSameDateTime($ucrmEvent->getEnd()->getDateTime(), $googleEvent->getEnd()->getDateTime())
            || $ucrmEvent->getSummary() !== $googleEvent->getSummary()
            || $ucrmEvent->getDescription() !== $googleEvent->getDescription()
            || $ucrmEvent->getLocation() !== $googleEvent->getLocation();
    }

    /**
     * This method is needed to unify timezone suffixes.
     * For example PHP RFC3339 has "+00:00" suffix, but Google uses "Z" suffix.
     * While both are correct according to RFC, we need this for comparison.
     */
    private function isSameDateTime(?string $date1, ?string $date2): bool
    {
        if ($date1 === null || $date2 === null) {
            return $date1 === $date2;
        }

        $utc = new \DateTimeZone('UTC');
        $date1 = DateTimeFactory::createFromFormat(\DateTime::RFC3339, $date1);
        $date1->setTimezone($utc);
        $date2 = DateTimeFactory::createFromFormat(\DateTime::RFC3339, $date2);
        $date2->setTimezone($utc);

        return $date1->format(\DateTime::RFC3339) === $date2->format(\DateTime::RFC3339);
    }
}
