<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Exception\GoogleCalendarException;

class GoogleCalendarFacade
{
    public function importEvent(\Google_Client $client, string $calendarId, \Google_Service_Calendar_Event $event): void
    {
        try {
            $service = new \Google_Service_Calendar($client);
            $service->events->import($calendarId, $event);
        } catch (\Exception $exception) {
            throw new GoogleCalendarException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function updateEvent(
        \Google_Client $client,
        string $calendarId,
        string $eventId,
        \Google_Service_Calendar_Event $event
    ): void {
        try {
            $service = new \Google_Service_Calendar($client);
            $service->events->update($calendarId, $eventId, $event);
        } catch (\Exception $exception) {
            throw new GoogleCalendarException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function deleteEvent(\Google_Client $client, string $calendarId, string $eventId): void
    {
        try {
            $service = new \Google_Service_Calendar($client);
            $service->events->delete($calendarId, $eventId);
        } catch (\Exception $exception) {
            throw new GoogleCalendarException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
