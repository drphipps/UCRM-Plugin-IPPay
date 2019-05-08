<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\GoogleCalendar;

use SchedulingBundle\Entity\Job;

class JobToEventConverter
{
    public function convert(Job $job, ?\Google_Service_Calendar_Event $event = null): ?\Google_Service_Calendar_Event
    {
        if (! $job->getDate()) {
            return null;
        }

        $event = $event ?? new \Google_Service_Calendar_Event();
        $event->setICalUID(Operation::UUID_PREFIX . $job->getUuid());
        $event->setStart($this->getGoogleDate($job->getDate()));
        if ($job->getDateEnd()) {
            $event->setEnd($this->getGoogleDate($job->getDateEnd()));
        } else {
            $event->setEnd(clone $event->getStart());
        }
        $event->setSummary($job->getTitle());
        $event->setDescription($job->getDescription());
        if ($job->getAddress()) {
            $event->setLocation($job->getAddress());
        }

        return $event;
    }

    private function getGoogleDate(\DateTimeInterface $dateTime): \Google_Service_Calendar_EventDateTime
    {
        $googleDateTime = new \Google_Service_Calendar_EventDateTime();
        $googleDateTime->setDateTime($dateTime->format(\DateTime::RFC3339));
        $googleDateTime->setTimeZone($dateTime->getTimezone()->getName());

        return $googleDateTime;
    }
}
