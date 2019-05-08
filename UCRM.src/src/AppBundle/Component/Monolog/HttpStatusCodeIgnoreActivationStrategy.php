<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Monolog;

use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpStatusCodeIgnoreActivationStrategy extends ErrorLevelActivationStrategy
{
    private const IGNORED_STATUS_CODES = [
        403, // Access Denied
        404, // Not Found
        405, // Method Not Allowed
    ];

    public function isHandlerActivated(array $record)
    {
        $isActivated = parent::isHandlerActivated($record);

        if (
            $isActivated
            && isset($record['context']['exception'])
            && $record['context']['exception'] instanceof HttpException
            && in_array($record['context']['exception']->getStatusCode(), self::IGNORED_STATUS_CODES, true)
        ) {
            return false;
        }

        return $isActivated;
    }
}
