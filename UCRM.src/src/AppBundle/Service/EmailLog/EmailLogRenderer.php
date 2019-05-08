<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\EmailLog;

use AppBundle\Entity\EmailLog;

class EmailLogRenderer
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function renderMessage(EmailLog $emailLog, bool $includeSubject, bool $truncateMessage): string
    {
        return $this->twig->render(
            'email_log/components/message.html.twig',
            [
                'emailLog' => $emailLog,
                'includeSubject' => $includeSubject,
                'truncateMessage' => $truncateMessage,
            ]
        );
    }

    public function renderRecipient(EmailLog $log): string
    {
        return $this->twig->render(
            'email_log/components/recipient.html.twig',
            [
                'log' => $log,
            ]
        );
    }
}
