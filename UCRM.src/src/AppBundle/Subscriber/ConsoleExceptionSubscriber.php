<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber;

use AppBundle\Service\ExceptionTracker;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ExceptionTracker
     */
    private $tracker;

    public function __construct(ExceptionTracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => 'handleConsoleError',
        ];
    }

    public function handleConsoleError(ConsoleErrorEvent $event)
    {
        $this->tracker->captureException($event->getError());
    }
}
