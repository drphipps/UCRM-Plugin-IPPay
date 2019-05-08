<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber;

use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TimezoneSubscriber implements EventSubscriberInterface
{
    /**
     * @var Options
     */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Options::EVENT_POST_REFRESH => 'onOptionsPostRefresh',
        ];
    }

    public function onOptionsPostRefresh(): void
    {
        date_default_timezone_set($this->options->get(Option::APP_TIMEZONE, 'UTC'));
    }
}
