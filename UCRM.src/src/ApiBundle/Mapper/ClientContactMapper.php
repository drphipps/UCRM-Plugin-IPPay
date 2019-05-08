<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ClientContactMap;
use ApiBundle\Map\ContactTypeMap;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\ContactType;

class ClientContactMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ClientContactMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return ClientContact::class;
    }

    /**
     * @param ClientContact    $entity
     * @param ClientContactMap $map
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof ClientContactMap) {
            throw new UnexpectedTypeException($map, ClientContactMap::class);
        }

        $this->mapField($entity, $map, 'email');
        $this->mapField($entity, $map, 'phone');
        $this->mapField($entity, $map, 'name');

        if ($map->isBilling !== null) {
            $billingType = $this->entityManager->find(ContactType::class, ContactType::IS_BILLING);
            if ($map->isBilling) {
                $entity->addType($billingType);
            } else {
                $entity->removeType($billingType);
            }
        }

        if ($map->isContact !== null) {
            $generalType = $this->entityManager->find(ContactType::class, ContactType::IS_CONTACT);
            if ($map->isContact) {
                $entity->addType($generalType);
            } else {
                $entity->removeType($generalType);
            }
        }

        /** @var ContactTypeMap $typeMap */
        foreach ($map->types ?? [] as $typeMap) {
            $contactType = $this->entityManager->getRepository(ContactType::class)->findOneBy(['name' => $typeMap->name]);

            if (! $contactType) {
                $contactType = new ContactType();
                $this->mapField($contactType, $typeMap, 'name');
            }

            $entity->addType($contactType);
        }
    }

    /**
     * @param ClientContact $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'clientId', $entity->getClient(), 'id');
        $this->reflectField($map, 'email', $entity->getEmail());
        $this->reflectField($map, 'phone', $entity->getPhone());
        $this->reflectField($map, 'name', $entity->getName());

        $contactTypes = [];
        $isBilling = false;
        $isContact = false;
        foreach ($entity->getTypes() as $contactType) {
            if ($contactType->getId() === ContactType::IS_BILLING) {
                $isBilling = true;
            } elseif ($contactType->getId() === ContactType::IS_CONTACT) {
                $isContact = true;
            }
            $contactTypeMap = new ContactTypeMap();
            $this->reflectField($contactTypeMap, 'id', $contactType->getId());
            $this->reflectField($contactTypeMap, 'name', $contactType->getName());
            $contactTypes[] = $contactTypeMap;
        }
        $this->reflectField($map, 'types', $contactTypes);

        $this->reflectField($map, 'isBilling', $isBilling);
        $this->reflectField($map, 'isContact', $isContact);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'client' => 'clientId',
        ];
    }
}
