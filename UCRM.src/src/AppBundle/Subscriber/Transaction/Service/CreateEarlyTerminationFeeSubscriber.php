<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Service;

use AppBundle\Entity\Fee;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Service\Fee\EarlyTerminationDetector;
use AppBundle\Service\Options;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateEarlyTerminationFeeSubscriber implements EventSubscriberInterface
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var EarlyTerminationDetector
     */
    private $earlyTerminationDetector;

    public function __construct(Options $options, EarlyTerminationDetector $earlyTerminationDetector)
    {
        $this->options = $options;
        $this->earlyTerminationDetector = $earlyTerminationDetector;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServiceAddEvent::class => 'handleServiceAddEvent',
            ServiceEditEvent::class => 'handleServiceEditEvent',
        ];
    }

    public function handleServiceAddEvent(ServiceAddEvent $event): void
    {
        $this->processService($event->getService());
    }

    public function handleServiceEditEvent(ServiceEditEvent $event): void
    {
        $this->processService($event->getService());
    }

    private function processService(Service $service): void
    {
        $service->calculateStatus();

        if (
            $service->getStatus() === Service::STATUS_ENDED
            && $this->earlyTerminationDetector->shouldCreateEarlyTerminationFee($service)
        ) {
            $this->createEarlyTerminationFee($service);
        }
    }

    private function createEarlyTerminationFee(Service $service): void
    {
        $fee = new Fee();
        $fee->setType(Fee::TYPE_EARLY_TERMINATION_FEE);
        $fee->setClient($service->getClient());
        $fee->setService($service);
        $fee->setCreatedDate(new \DateTime());
        $fee->setInvoiceLabel($this->options->get(Option::EARLY_TERMINATION_FEE_INVOICE_LABEL));
        $fee->setName($this->options->get(Option::EARLY_TERMINATION_FEE_INVOICE_LABEL));
        $fee->setTaxable($this->options->get(Option::EARLY_TERMINATION_FEE_TAXABLE));
        $fee->setInvoiced(false);
        $fee->setPrice($service->getEarlyTerminationFeePrice());

        $service->setEarlyTerminationFee($fee);
    }
}
