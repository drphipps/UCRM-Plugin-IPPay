<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber;

use AppBundle\Handler\WebrootHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This subscriber is used to provide webroot file download if possible for the request.
 *
 * Needs to be handled with the GetResponseForExceptionEvent, because the allowCustomResponseCode()
 * method is the only way, how to prevent Symfony from changing the status code on the Response
 * when handling an exception.
 */
class WebrootDownloadSubscriber implements EventSubscriberInterface
{
    /**
     * @var WebrootHandler
     */
    private $webrootHandler;

    public function __construct(WebrootHandler $webrootHandler)
    {
        $this->webrootHandler = $webrootHandler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        if ($response = $this->webrootHandler->downloadWebrootFile($event->getRequest())) {
            $event->allowCustomResponseCode();
            $event->setResponse($response);
        }
    }
}
