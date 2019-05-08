<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Email;

use AppBundle\Entity\EmailLog;
use AppBundle\RabbitMq\Email\SendEmailMessage;
use AppBundle\Util\Helpers;
use RabbitMqBundle\RabbitMqEnqueuer;

class EmailEnqueuer
{
    public const PRIORITY_LOW = 0;
    public const PRIORITY_MEDIUM = 1;
    public const PRIORITY_HIGH = 2;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var EmailLogger
     */
    private $emailLogger;

    /**
     * @var EmailFilesystem
     */
    private $emailFilesystem;

    /**
     * @var EmailSanitizer
     */
    private $emailSanitizer;

    public function __construct(
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        EmailLogger $emailLogger,
        EmailFilesystem $emailFilesystem,
        EmailSanitizer $emailSanitizer
    ) {
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->emailLogger = $emailLogger;
        $this->emailFilesystem = $emailFilesystem;
        $this->emailSanitizer = $emailSanitizer;
    }

    public function enqueue(
        \Swift_Message $swiftMessage,
        int $priority,
        ?string $eventClass = null,
        ?array $eventData = null
    ): EmailLog {
        $this->emailSanitizer->sanitizeAddressFields($swiftMessage);

        if (! Helpers::isDemo()) {
            $this->rabbitMqEnqueuer->enqueue(
                new SendEmailMessage(
                    $this->emailFilesystem->saveToSpool($swiftMessage),
                    $priority,
                    $eventClass,
                    $eventData
                )
            );
        }

        return $this->emailLogger->log($swiftMessage);
    }
}
