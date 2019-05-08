<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Service\Client\ClientBalanceFormatter;
use AppBundle\Util\Formatter;
use AppBundle\Util\Notification;
use Symfony\Component\Translation\TranslatorInterface;

class NotificationFactory
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ClientBalanceFormatter
     */
    private $clientBalanceFormatter;

    public function __construct(
        Formatter $formatter,
        TranslatorInterface $translator,
        ClientBalanceFormatter $clientBalanceFormatter
    ) {
        $this->formatter = $formatter;
        $this->translator = $translator;
        $this->clientBalanceFormatter = $clientBalanceFormatter;
    }

    public function create(): Notification
    {
        return new Notification($this->formatter, $this->translator, $this->clientBalanceFormatter);
    }
}
