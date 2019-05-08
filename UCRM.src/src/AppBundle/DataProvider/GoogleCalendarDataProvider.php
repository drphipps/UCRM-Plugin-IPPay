<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Exception\GoogleCalendarException;
use AppBundle\Service\GoogleCalendar\Operation;
use Nette\Utils\Strings;

class GoogleCalendarDataProvider
{
    public function getWritableCalendars(\Google_Client $client): array
    {
        try {
            $service = new \Google_Service_Calendar($client);
            $list = $service->calendarList->listCalendarList(
                [
                    'minAccessRole' => 'writer',
                ]
            );
        } catch (\Exception $exception) {
            throw new GoogleCalendarException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $calendars = [];
        /** @var \Google_Service_Calendar_CalendarListEntry $calendar */
        foreach ($list as $calendar) {
            $calendars[$calendar->id] = $calendar->summary;
        }

        return $calendars;
    }

    public function getAllManagedEvents(
        \Google_Client $googleClient,
        string $calendarId,
        \DateTimeInterface $timeMin,
        \DateTimeInterface $timeMax
    ): array {
        $events = [];
        do {
            $eventsCollection = $this->getEvents(
                $googleClient,
                $calendarId,
                $timeMin,
                $timeMax,
                $pageToken ?? null
            );
            $events = array_merge($events, (array) $eventsCollection->getItems());
        } while ($pageToken = $eventsCollection->getNextPageToken());

        $events = array_filter(
            $events,
            function (\Google_Service_Calendar_Event $event) {
                // We are not interested in events not managed by UCRM.

                return Strings::startsWith($event->getICalUID(), Operation::UUID_PREFIX);
            }
        );

        return $events;
    }

    public function getEventByUuid(
        \Google_Client $client,
        string $calendarId,
        string $uuid
    ): ?\Google_Service_Calendar_Event {
        try {
            $service = new \Google_Service_Calendar($client);
            $options = [
                'showDeleted' => true,
                'iCalUID' => Operation::UUID_PREFIX . $uuid,
                'maxResults' => 1,
            ];
            $events = $service->events->listEvents(
                $calendarId,
                $options
            );
            $items = $events->getItems();
        } catch (\Exception $exception) {
            throw new GoogleCalendarException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return reset($items) ?: null;
    }

    private function getEvents(
        \Google_Client $client,
        string $calendarId,
        \DateTimeInterface $timeMin,
        \DateTimeInterface $timeMax,
        ?string $pageToken
    ): \Google_Service_Calendar_Events {
        try {
            $service = new \Google_Service_Calendar($client);
            $options = [
                'showDeleted' => true,
                'timeMin' => $timeMin->format(\DateTime::RFC3339),
                'timeMax' => $timeMax->format(\DateTime::RFC3339),
            ];
            if ($pageToken) {
                $options['pageToken'] = $pageToken;
            }
            $events = $service->events->listEvents(
                $calendarId,
                $options
            );
        } catch (\Exception $exception) {
            throw new GoogleCalendarException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $events;
    }
}
