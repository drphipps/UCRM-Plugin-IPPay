<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\GoogleCalendar;

use AppBundle\Facade\GoogleCalendarFacade;
use AppBundle\Service\GoogleCalendar\JobToEventConverter;
use AppBundle\Service\GoogleCalendar\Synchronizer;
use Psr\Log\LoggerInterface;

class SynchronizerFactory
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

    public function __construct(
        GoogleCalendarFacade $googleCalendarFacade,
        JobToEventConverter $jobToEventConverter,
        LoggerInterface $logger
    ) {
        $this->googleCalendarFacade = $googleCalendarFacade;
        $this->jobToEventConverter = $jobToEventConverter;
        $this->logger = $logger;
    }

    public function create(\Google_Client $client, string $calendarId): Synchronizer
    {
        return new Synchronizer(
            $this->googleCalendarFacade,
            $this->jobToEventConverter,
            $this->logger,
            $client,
            $calendarId
        );
    }
}
