<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Entity\Client;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Service\Options;
use DateTime;
use Symfony\Component\Translation\TranslatorInterface;

class ServiceFactory
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

    public function create(Client $client): Service
    {
        $service = new Service();

        $today = new DateTime('today midnight');

        $service->setClient($client);
        $service->setActiveFrom($today);
        $service->setInvoicingStart($today);
        $startDay = $this->options->get(Option::INVOICE_PERIOD_START_DAY);
        if (Option::INVOICE_PERIOD_START_DAY_TODAY === $startDay) {
            $startDay = (int) $today->format('j');
        }
        $service->setInvoicingPeriodStartDay($startDay < 29 ? $startDay : 31);

        $service->setStreet1($client->getStreet1());
        $service->setStreet2($client->getStreet2());
        $service->setCity($client->getCity());
        $service->setCountry($client->getCountry());
        $service->setState($client->getState());
        $service->setZipCode($client->getZipCode());
        $service->setAddressGpsLat($client->getAddressGpsLat());
        $service->setAddressGpsLon($client->getAddressGpsLon());
        $service->setIsAddressGpsCustom($client->isAddressGpsCustom());
        $service->setContractLengthType(Service::CONTRACT_OPEN);
        $service->setDiscountInvoiceLabel(
            $this->options->get(Option::DISCOUNT_INVOICE_LABEL) ?: $this->translator->trans('Discount')
        );
        $service->setInvoicingPeriodType($this->options->get(Option::INVOICING_PERIOD_TYPE));
        $service->setNextInvoicingDayAdjustment($this->options->get(Option::SERVICE_INVOICING_DAY_ADJUSTMENT));

        return $service;
    }
}
