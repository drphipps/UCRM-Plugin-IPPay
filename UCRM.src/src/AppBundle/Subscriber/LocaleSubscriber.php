<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber;

use AppBundle\Entity\Option;
use AppBundle\Entity\User;
use AppBundle\Service\Options;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(Options $options, SessionInterface $session)
    {
        $this->options = $options;
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Needs to be called before Symfony\Component\HttpKernel\EventListener\TranslatorListener.
            KernelEvents::REQUEST => ['onKernelRequest', 12],
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User && null !== $user->getLocale()) {
            $this->session->set('_locale', $user->getLocale()->getCode());
        }
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $locale = $this->session->get('_locale') ?: $this->options->get(Option::APP_LOCALE);
        $request->setLocale($locale);
    }
}
