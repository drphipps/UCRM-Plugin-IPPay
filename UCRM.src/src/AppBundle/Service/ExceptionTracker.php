<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Option;
use AppBundle\Exception\ImapConnectionException;
use Doctrine\DBAL\Exception\ConnectionException;
use Elastica\Exception\Connection\HttpException;
use Nette\Utils\Strings;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ExceptionTracker
{
    // When adding skipped exceptions, extend them in UCRM stats application as well and vice versa.
    private const SKIPPED_EXCEPTION_CLASSES = [
        \Swift_TransportException::class,
        ConnectionException::class,
        AccessDeniedHttpException::class,
        ImapConnectionException::class,
        HttpException::class,
    ];

    private const SKIPPED_EXCEPTION_MESSAGES = [
        '~Elasticsearch down~im',
        '~the database system is shutting down~im',
        '~Integrity check failed\.~im',
        '~server closed the connection unexpectedly~im',
        '~unable to connect to tcp\://rabbitmq\:5672~im',
        '~php_network_getaddresses\: getaddrinfo failed\:~im',
        '~bytes failed with errno\=104 Connection reset by peer~im',
        '~Broken pipe or closed connection~im',
        '~No space left on device.*~im',
        '~The process "fping ~im',
        '~CONNECTION_FORCED \- broker forced connection closure with reason \'shutdown\'~im',
        '~does not comply with RFC 2822~im',
        '~Failed to authenticate on SMTP server with username~im',
        '~No route found for "OPTIONS ~im',
        '~No route found for "PROPFIND ~im',
        '~NOT_FOUND - failed to perform operation on queue.*due to timeout~im',
        '~Error in one or more bulk request actions.*caused no such index~im',
    ];

    /**
     * @var Request|null
     */
    private $request;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var SentryClient
     */
    private $sentryClient;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        RequestStack $requestStack,
        Options $options,
        SentryClient $sentryClient,
        string $environment
    ) {
        $this->request = $requestStack->getMasterRequest();
        $this->options = $options;
        $this->sentryClient = $sentryClient;
        $this->environment = $environment;
    }

    /**
     * @param \Throwable|\Exception $exception
     */
    public function captureException($exception): ?string
    {
        if ($this->environment !== 'prod') {
            return null;
        }

        if (! $this->options->get(Option::ERROR_REPORTING, true)) {
            return null;
        }

        if ($this->isExceptionSkipped($exception)) {
            return null;
        }

        return $this->sentryClient->captureException(
            $exception,
            [
                'tags' => [
                    'remote_uri' => $this->request ? $this->request->getUri() : null,
                    'remote' => 'UCRM',
                ],
                'extra' => [
                    'body' => $this->request && ! $this->requestContainsSensitiveData()
                        ? $this->request->getContent()
                        : null,
                    'referer' => $this->request ? $this->request->headers->get('referer') : null,
                    // Doctrine sometimes generates long exception messages which get clipped so we copy the whole message here.
                    'message' => $exception->getMessage(),
                ],
            ]
        );
    }

    /**
     * @param \Throwable|\Exception $exception
     */
    private function isExceptionSkipped($exception): bool
    {
        foreach (self::SKIPPED_EXCEPTION_MESSAGES as $skippedMessage) {
            if (Strings::startsWith($skippedMessage, '~')) {
                if (Strings::match(Strings::replace($exception->getMessage(), '/\s+/', ' '), $skippedMessage)) {
                    return true;
                }
            } elseif (Strings::lower($skippedMessage) === Strings::lower(trim($exception->getMessage()))) {
                return true;
            }
        }

        foreach (self::SKIPPED_EXCEPTION_CLASSES as $skippedException) {
            if (
                is_a($exception, $skippedException)
                || is_subclass_of($exception, $skippedException)
                || (
                    $exception instanceof FlattenException
                    && (
                        $exception->getClass() === $skippedException
                        || is_subclass_of($exception->getClass(), $skippedException)
                    )
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function requestContainsSensitiveData(): bool
    {
        return $this->request && Strings::contains($this->request->getUri(), 'online-payment') !== false;
    }
}
