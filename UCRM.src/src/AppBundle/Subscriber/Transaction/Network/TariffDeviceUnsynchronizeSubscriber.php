<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Network;

use AppBundle\Component\QoS\QoSSynchronizationManager;
use AppBundle\Entity\Option;
use AppBundle\Event\Tariff\TariffEditEvent;
use AppBundle\Service\Options;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TariffDeviceUnsynchronizeSubscriber implements EventSubscriberInterface
{
    /**
     * @var QoSSynchronizationManager
     */
    private $synchronizationManager;

    /**
     * @var Options
     */
    private $options;

    public function __construct(QoSSynchronizationManager $synchronizationManager, Options $options)
    {
        $this->synchronizationManager = $synchronizationManager;
        $this->options = $options;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TariffEditEvent::class => 'handleTariffEditEvent',
        ];
    }

    public function handleTariffEditEvent(TariffEditEvent $event): void
    {
        $tariff = $event->getTariff();
        $tariffBeforeUpdate = $event->getTariffBeforeUpdate();

        if (
            ! $this->options->get(Option::QOS_ENABLED)
            || (
                round($tariffBeforeUpdate->getUploadSpeed(), 4) === round($tariff->getUploadSpeed(), 4)
                && round($tariffBeforeUpdate->getDownloadSpeed(), 4) === round($tariff->getDownloadSpeed(), 4)
                && $tariffBeforeUpdate->getUploadBurst() === $tariff->getUploadBurst()
                && $tariffBeforeUpdate->getDownloadBurst() === $tariff->getDownloadBurst()
            )
        ) {
            return;
        }

        if ($this->options->get(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_GATEWAY) {
            $this->synchronizationManager->markAllGatewaysUnsynchronized();
        } else {
            $this->synchronizationManager->markTariffDevicesUnsynchronized($event->getTariff());
        }
    }
}
