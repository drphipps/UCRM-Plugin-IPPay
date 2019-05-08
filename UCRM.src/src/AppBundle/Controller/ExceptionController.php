<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Service\ExceptionTracker;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController as ExceptionControllerTwig;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ExceptionController extends ExceptionControllerTwig
{
    private const ENV_PROD = 'prod';
    private const ENV_TEST = 'test';

    /**
     * @var ExceptionTracker
     */
    private $tracker;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string|null
     */
    private $sentryDsnFrontend;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        \Twig_Environment $twig,
        bool $debug,
        ExceptionTracker $tracker,
        TokenStorageInterface $tokenStorage,
        string $environment,
        ?string $sentryDsnFrontend = null
    ) {
        parent::__construct($twig, $debug);

        $this->tracker = $tracker;
        $this->environment = $environment;
        $this->sentryDsnFrontend = $sentryDsnFrontend;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param \Throwable|\Exception $originalException
     */
    public function showAction(
        Request $request,
        FlattenException $exception,
        DebugLoggerInterface $logger = null,
        $originalException = null
    ): Response {
        if (
            $this->environment === self::ENV_TEST
            && ! is_subclass_of($originalException, HttpExceptionInterface::class)
        ) {
            throw new \Exception();
        }

        $eventId = null;
        if (
            ! in_array($exception->getStatusCode(), [403, 404, 405], true)
            && $this->environment === self::ENV_PROD
        ) {
            $isAdmin = $this->tokenStorage->getToken()
                && $this->tokenStorage->getToken()->getUser() instanceof User
                && $this->tokenStorage->getToken()->getUser()->isAdmin();

            $eventId = $this->tracker->captureException($originalException);
            $eventId = $isAdmin ? $eventId : null;
        }

        $currentContent = $this->getAndCleanOutputBuffering((int) $request->headers->get('X-Php-Ob-Level', '-1'));
        $showException = $request->attributes->get('showException', $this->debug);

        $code = $exception->getStatusCode();

        return new Response(
            $this->twig->render(
                $this->findTemplate($request, $request->getRequestFormat(), $code, $showException),
                [
                    'status_code' => $code,
                    'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                    'exception' => $exception,
                    'logger' => $logger,
                    'currentContent' => $currentContent,
                    'eventId' => $eventId,
                    'sentryDsnFrontend' => $this->sentryDsnFrontend,
                ]
            )
        );
    }
}
