<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber;

use AppBundle\Entity\Option;
use AppBundle\Exception\OptionNotFoundException;
use AppBundle\Service\Options;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Separated from LocaleSubscriber to prevent loading of NativeFileSessionHandler in console.
 *
 * The issue was an ini_set call after headers were already sent.
 */
class LocaleConsoleSubscriber implements EventSubscriberInterface
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(Options $options, TranslatorInterface $translator)
    {
        $this->options = $options;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    public function onConsoleCommand(): void
    {
        try {
            $this->translator->setLocale($this->options->get(Option::APP_LOCALE));
        } catch (DriverException | OptionNotFoundException $e) {
            // Silently continue
        }
    }
}
