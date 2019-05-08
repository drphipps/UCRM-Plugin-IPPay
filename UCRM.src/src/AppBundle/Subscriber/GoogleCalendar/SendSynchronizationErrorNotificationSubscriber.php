<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\GoogleCalendar;

use AppBundle\Component\HeaderNotification\HeaderNotifier;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Event\GoogleCalendar\SynchronizationErrorEvent;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SendSynchronizationErrorNotificationSubscriber implements EventSubscriberInterface
{
    /**
     * @var HeaderNotifier
     */
    private $headerNotifier;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(HeaderNotifier $headerNotifier, TranslatorInterface $translator)
    {
        $this->headerNotifier = $headerNotifier;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SynchronizationErrorEvent::class => 'handleSynchronizationErrorEvent',
        ];
    }

    public function handleSynchronizationErrorEvent(SynchronizationErrorEvent $event): void
    {
        $user = $event->getUser();
        $exception = $event->getException();

        if ($user->isGoogleSynchronizationErrorNotificationSent()) {
            return;
        }

        try {
            $exceptionMessage = Json::decode($exception->getMessage());
            $errors = [];
            if (isset($exceptionMessage->error->errors)) {
                foreach ($exceptionMessage->error->errors as $error) {
                    if (isset($error->message)) {
                        $errors[] = $error->message;
                    }
                }
            }

            $notification = $errors ? implode(', ', $errors) : $exception->getMessage();
        } catch (JsonException $jsonException) {
            $notification = $exception->getMessage();
        }

        $this->headerNotifier->sendToAdmin(
            $user,
            HeaderNotification::TYPE_WARNING,
            $this->translator->trans('Google Calendar synchronization unsuccessful.'),
            $notification
        );
    }
}
