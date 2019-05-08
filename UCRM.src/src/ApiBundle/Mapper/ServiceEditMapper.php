<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ServiceEditMap;
use AppBundle\Entity\Country;
use AppBundle\Entity\Service;
use AppBundle\Entity\State;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;

class ServiceEditMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ServiceEditMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Service::class;
    }

    /**
     * @param Service $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof ServiceEditMap) {
            throw new UnexpectedTypeException($map, ServiceEditMap::class);
        }

        $this->mapField($entity, $map, 'tariff', 'servicePlanId', Tariff::class);
        $this->mapField($entity, $map, 'tariffPeriod', 'servicePlanPeriodId', TariffPeriod::class);
        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'individualPrice', 'price');
        $this->mapField($entity, $map, 'invoicingPeriodType');
        $this->mapField($entity, $map, 'invoiceLabel');
        $this->mapField($entity, $map, 'activeTo');
        $this->mapField($entity, $map, 'nextInvoicingDayAdjustment');
        $this->mapField($entity, $map, 'invoicingSeparately');
        $this->mapField($entity, $map, 'sendEmailsAutomatically');
        $this->mapField($entity, $map, 'useCreditAutomatically');
        $this->mapField($entity, $map, 'contractId');
        $this->mapField($entity, $map, 'contractLengthType');
        $this->mapField($entity, $map, 'minimumContractLengthMonths');
        $this->mapField($entity, $map, 'contractEndDate');
        $this->mapField($entity, $map, 'street1');
        $this->mapField($entity, $map, 'street2');
        $this->mapField($entity, $map, 'city');
        $this->mapField($entity, $map, 'country', 'countryId', Country::class);
        $this->mapField($entity, $map, 'state', 'stateId', State::class);
        $this->mapField($entity, $map, 'zipCode');
        $this->mapField($entity, $map, 'addressGpsLat');
        $this->mapField($entity, $map, 'addressGpsLon');
        $this->mapField($entity, $map, 'note');
        $this->mapField($entity, $map, 'fccBlockId');
    }

    /**
     * @param Service $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        throw new \LogicException('Only used for updating, never for GET.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'country' => 'countryId',
            'state' => 'stateId',
            'tariff' => 'servicePlanId',
            'tariffPeriod' => 'servicePlanPeriodId',
        ];
    }
}
