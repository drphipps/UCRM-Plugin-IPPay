<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\EventListener;

use AppBundle\Service\SentryClient;
use Sentry\SentryBundle\EventListener\ExceptionListener;
use Sentry\SentryBundle\EventListener\SentryExceptionListenerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * We need to override original Sentry ExceptionListener to disable automatic sending of errors.
 * Our sending of errors is handled in ExceptionController and ConsoleExceptionSubscriber via
 * ExceptionTracker to handle error reporting options and skipped exceptions.
 */
class SentryExceptionListener extends ExceptionListener implements SentryExceptionListenerInterface
{
    public function __construct(
        SentryClient $client,
        EventDispatcherInterface $dispatcher,
        RequestStack $requestStack,
        array $skipCapture,
        TokenStorageInterface $tokenStorage = null,
        AuthorizationCheckerInterface $authorizationChecker = null
    ) {
        parent::__construct($client, $dispatcher, $requestStack, $skipCapture, $tokenStorage, $authorizationChecker);
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
    }

    public function onConsoleException(ConsoleExceptionEvent $event): void
    {
    }
}
