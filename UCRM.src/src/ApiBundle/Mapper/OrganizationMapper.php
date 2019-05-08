<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\OrganizationMap;
use AppBundle\Entity\Country;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Organization;
use AppBundle\Entity\State;

class OrganizationMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return OrganizationMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Organization::class;
    }

    /**
     * @param Organization $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'registrationNumber');
        $this->mapField($entity, $map, 'taxId');
        $this->mapField($entity, $map, 'phone');
        $this->mapField($entity, $map, 'email');
        $this->mapField($entity, $map, 'website');
        $this->mapField($entity, $map, 'street1');
        $this->mapField($entity, $map, 'street2');
        $this->mapField($entity, $map, 'city');
        $this->mapField($entity, $map, 'state', 'stateId', State::class);
        $this->mapField($entity, $map, 'country', 'countryId', Country::class);
        $this->mapField($entity, $map, 'currency', 'currencyId', Currency::class);
        $this->mapField($entity, $map, 'zipCode');
        $this->mapField($entity, $map, 'selected');
    }

    /**
     * @param Organization $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'registrationNumber', $entity->getRegistrationNumber());
        $this->reflectField($map, 'taxId', $entity->getTaxId());
        $this->reflectField($map, 'phone', $entity->getPhone());
        $this->reflectField($map, 'email', $entity->getEmail());
        $this->reflectField($map, 'website', $entity->getWebsite());
        $this->reflectField($map, 'street1', $entity->getStreet1());
        $this->reflectField($map, 'street2', $entity->getStreet2());
        $this->reflectField($map, 'city', $entity->getCity());
        $this->reflectField($map, 'stateId', $entity->getState(), 'id');
        $this->reflectField($map, 'countryId', $entity->getCountry(), 'id');
        $this->reflectField($map, 'currencyId', $entity->getCurrency(), 'id');
        $this->reflectField($map, 'zipCode', $entity->getZipCode());
        $this->reflectField($map, 'selected', $entity->getSelected());
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'country' => 'countryId',
            'state' => 'stateId',
            'currency' => 'currencyId',
        ];
    }
}
