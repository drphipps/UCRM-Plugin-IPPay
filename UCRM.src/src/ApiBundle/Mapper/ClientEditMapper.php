<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ClientAttributeMap;
use ApiBundle\Map\ClientContactMap;
use ApiBundle\Map\ClientEditMap;
use ApiBundle\Map\ContactTypeMap;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientAttribute;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\ContactType;
use AppBundle\Entity\Country;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\Organization;
use AppBundle\Entity\State;
use AppBundle\Entity\Tax;

class ClientEditMapper extends AbstractMapper
{
    protected function getMapClassName(): string
    {
        return ClientEditMap::class;
    }

    protected function getEntityClassName(): string
    {
        return Client::class;
    }

    /**
     * @param ClientEditMap $map
     * @param Client        $entity
     *
     * @throws \ApiBundle\Exception\UnexpectedTypeException
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof ClientEditMap) {
            throw new UnexpectedTypeException($map, ClientEditMap::class);
        }

        $this->mapField($entity, $map, 'userIdent');
        $this->mapField($entity, $map, 'previousIsp');
        $this->mapField($entity, $map, 'isLead');
        $this->mapField($entity, $map, 'clientType');
        $this->mapField($entity, $map, 'companyName');
        $this->mapField($entity, $map, 'companyRegistrationNumber');
        $this->mapField($entity, $map, 'companyTaxId');
        $this->mapField($entity, $map, 'companyWebsite');
        $this->mapField($entity, $map, 'street1');
        $this->mapField($entity, $map, 'street2');
        $this->mapField($entity, $map, 'city');
        $this->mapField($entity, $map, 'country', 'countryId', Country::class);
        $this->mapField($entity, $map, 'state', 'stateId', State::class);
        $this->mapField($entity, $map, 'zipCode');
        $this->mapField($entity, $map, 'invoiceStreet1');
        $this->mapField($entity, $map, 'invoiceStreet2');
        $this->mapField($entity, $map, 'invoiceCity');
        $this->mapField($entity, $map, 'invoiceCountry', 'invoiceCountryId', Country::class);
        $this->mapField($entity, $map, 'invoiceState', 'invoiceStateId', State::class);
        $this->mapField($entity, $map, 'invoiceZipCode');
        $this->mapField($entity, $map, 'invoiceAddressSameAsContact');
        $this->mapField($entity, $map, 'note');
        $this->mapField($entity, $map, 'sendInvoiceByPost');
        $this->mapField($entity, $map, 'invoiceMaturityDays');
        $this->mapField($entity, $map, 'stopServiceDue');
        $this->mapField($entity, $map, 'stopServiceDueDays');
        $this->mapField($entity, $map, 'organization', 'organizationId', Organization::class);
        $this->mapField($entity, $map, 'tax1', 'tax1Id', Tax::class);
        $this->mapField($entity, $map, 'tax2', 'tax2Id', Tax::class);
        $this->mapField($entity, $map, 'tax3', 'tax3Id', Tax::class);
        $this->mapField($entity, $map, 'registrationDate');
        $this->mapField($entity, $map, 'companyContactFirstName');
        $this->mapField($entity, $map, 'companyContactLastName');
        $this->mapField($entity, $map, 'addressGpsLat');
        $this->mapField($entity, $map, 'addressGpsLon');
        $this->mapField($entity, $map, 'generateProformaInvoices');

        $user = $entity->getUser();

        $this->mapField($user, $map, 'isActive');
        $this->mapField($user, $map, 'firstName');
        $this->mapField($user, $map, 'lastName');
        $this->mapField($user, $map, 'username');
        $this->mapField($user, $map, 'avatarColor');
        $this->mapField($user, $map, 'plainPassword', 'password');

        $entity->setUser($user);

        /** @var ClientContactMap $itemMap */
        foreach ($map->contacts ?? [] as $itemMap) {
            $contact = new ClientContact();
            $contact->setClient($entity);

            $this->mapField($contact, $itemMap, 'email');
            $this->mapField($contact, $itemMap, 'phone');
            $this->mapField($contact, $itemMap, 'name');

            if ($itemMap->isBilling) {
                $contact->addType($this->entityManager->getRepository(ContactType::class)->find(ContactType::IS_BILLING));
            }
            if ($itemMap->isContact) {
                $contact->addType($this->entityManager->getRepository(ContactType::class)->find(ContactType::IS_CONTACT));
            }

            /** @var ContactTypeMap $typeMap */
            foreach ($itemMap->types ?? [] as $typeMap) {
                $contactType = $this->entityManager->getRepository(ContactType::class)->findOneBy(['name' => $typeMap->name]);

                if (! $contactType) {
                    $contactType = new ContactType();
                    $this->mapField($contactType, $typeMap, 'name');
                }

                $contact->addType($contactType);
            }

            $entity->addContact($contact);
        }

        $clientAttributes = [];
        foreach ($entity->getAttributes() as $attribute) {
            $clientAttributes[$attribute->getAttribute()->getId()] = $attribute;
        }

        /** @var ClientAttributeMap $attributeMap */
        foreach ($map->attributes ?? [] as $attributeMap) {
            $attribute = $clientAttributes[$attributeMap->customAttributeId] ?? null;

            if ($attributeMap->value !== null && $attributeMap->value !== '') {
                if (! $attribute) {
                    $attribute = new ClientAttribute();
                    $entity->addAttribute($attribute);
                }
                $attribute->setClient($entity);

                $this->mapField($attribute, $attributeMap, 'value');
                $this->mapField($attribute, $attributeMap, 'attribute', 'customAttributeId', CustomAttribute::class);
            } elseif ($attribute) {
                $entity->removeAttribute($attribute);
            }
        }
    }

    /**
     * @param Client $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        throw new \LogicException('Only used for updating, never for GET.');
    }

    public function getFieldsDifference(): array
    {
        return [
            'country' => 'countryId',
            'state' => 'stateId',
            'invoiceCountry' => 'invoiceCountryId',
            'invoiceState' => 'invoiceStateId',
            'organization' => 'organizationId',
            'tax1' => 'tax1Id',
            'tax2' => 'tax2Id',
            'tax3' => 'tax3Id',
            'user.isActive' => 'isActive',
            'user.firstName' => 'firstName',
            'user.lastName' => 'lastName',
            'user.username' => 'username',
            'user.avatarColor' => 'avatarColor',
            'user.plainPassword' => 'password',
        ];
    }
}
