<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Email;

use AppBundle\Service\SandboxAwareMailer;

class EmailDirectSender
{
    public const PRIORITY_LOW = 0;
    public const PRIORITY_MEDIUM = 1;
    public const PRIORITY_HIGH = 2;

    /**
     * @var EmailLogger
     */
    private $emailLogger;

    /**
     * @var SandboxAwareMailer
     */
    private $sandboxAwareMailer;

    /**
     * @var EmailSanitizer
     */
    private $emailSanitizer;

    public function __construct(
        SandboxAwareMailer $sandboxAwareMailer,
        EmailLogger $emailLogger,
        EmailSanitizer $emailSanitizer
    ) {
        $this->sandboxAwareMailer = $sandboxAwareMailer;
        $this->emailLogger = $emailLogger;
        $this->emailSanitizer = $emailSanitizer;
    }

    /**
     * Send without going through the queue, but with logging enabled.
     */
    public function send(\Swift_Message $swiftMessage): int
    {
        $this->emailSanitizer->sanitizeAddressFields($swiftMessage);

        $loggedEmail = $this->emailLogger->log($swiftMessage);
        $this->sandboxAwareMailer->send($swiftMessage);

        return $loggedEmail->getId();
    }
}
