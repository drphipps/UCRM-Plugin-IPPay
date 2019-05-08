<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\EventListener;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener as SymfonyExceptionListener;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class ExceptionListener extends SymfonyExceptionListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    protected function duplicateRequest(\Exception $exception, Request $request)
    {
        // Extended attributes with original exception.
        $attributes = [
            '_controller' => $this->controller,
            'exception' => FlattenException::create($exception),
            'logger' => $this->logger instanceof DebugLoggerInterface ? $this->logger : null,
            'originalException' => $exception,
        ];
        $request = $request->duplicate(null, null, $attributes);
        $request->setMethod('GET');

        return $request;
    }
}
