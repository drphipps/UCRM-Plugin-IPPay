<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Handler\Notification;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

class NotificationTemplatePreviewHandler
{
    public const SESSION_NOTIFICATION_TEMPLATE_BODY = 'notificationTemplateBody';
    public const SESSION_NOTIFICATION_TEMPLATE_HEADING = 'notificationTemplateHeading';
    public const SESSION_NOTIFICATION_TEMPLATE_EXTRA_CSS = 'notificationTemplateExtraCss';

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(SessionInterface $session, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->translator = $translator;
    }

    public function setTemplateHeading(string $heading): void
    {
        $this->session->set(self::SESSION_NOTIFICATION_TEMPLATE_HEADING, $heading);
    }

    public function setTemplateBody(string $body): void
    {
        $this->session->set(self::SESSION_NOTIFICATION_TEMPLATE_BODY, $body);
    }

    public function setExtraCss(string $css): void
    {
        $this->session->set(self::SESSION_NOTIFICATION_TEMPLATE_EXTRA_CSS, $css);
    }

    public function getTemplateHeading(): ?string
    {
        $heading = $this->session->get(self::SESSION_NOTIFICATION_TEMPLATE_HEADING);
        $this->session->remove(self::SESSION_NOTIFICATION_TEMPLATE_HEADING);

        return $heading;
    }

    public function getTemplateBody(): string
    {
        $body = $this->session->get(
            self::SESSION_NOTIFICATION_TEMPLATE_BODY,
            $this->translator->trans('Session expired. Please, close modal window and try again.')
        );
        $this->session->remove(self::SESSION_NOTIFICATION_TEMPLATE_BODY);

        return $body;
    }

    public function getExtraCss(): string
    {
        $body = $this->session->get(
            self::SESSION_NOTIFICATION_TEMPLATE_EXTRA_CSS,
            ''
        );
        $this->session->remove(self::SESSION_NOTIFICATION_TEMPLATE_EXTRA_CSS);

        return $body;
    }
}
