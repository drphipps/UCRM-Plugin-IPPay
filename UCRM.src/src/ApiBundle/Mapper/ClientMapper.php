<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Component\Validator\ValidationErrorCollector;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ClientAttributeMap;
use ApiBundle\Map\ClientBankAccountMap;
use ApiBundle\Map\ClientContactMap;
use ApiBundle\Map\ClientMap;
use ApiBundle\Map\ClientTagMap;
use ApiBundle\Map\ContactTypeMap;
use AppBundle\Entity\Client;
use AppBundle\Entity\ContactType;
use AppBundle\Entity\Option;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Security\SpecialPermission;
use AppBundle\Service\Options;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;

class ClientMapper extends AbstractMapper
{
    /**
     * @var Options
     */
    private $options;

    public function __construct(
        EntityManagerInterface $entityManager,
        Reader $reader,
        ValidationErrorCollector $errorCollector,
        PermissionGrantedChecker $permissionGrantedChecker,
        Options $options
    ) {
        parent::__construct($entityManager, $reader, $errorCollector, $permissionGrantedChecker);

        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ClientMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Client::class;
    }

    /**
     * @param ClientMap $map
     * @param Client    $entity
     *
     * @throws \ApiBundle\Exception\UnexpectedTypeException
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        throw new \LogicException('Only used for getting, never for updating.');
    }

    /**
     * @param Client $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var ClientMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'userIdent', $entity->getUserIdent());
        $this->reflectField($map, 'previousIsp', $entity->getPreviousIsp());
        $this->reflectField($map, 'isLead', $entity->getIsLead());
        $this->reflectField($map, 'clientType', $entity->getClientType());
        $this->reflectField($map, 'companyName', $entity->getCompanyName());
        $this->reflectField($map, 'companyRegistrationNumber', $entity->getCompanyRegistrationNumber());
        $this->reflectField($map, 'companyTaxId', $entity->getCompanyTaxId());
        $this->reflectField($map, 'companyWebsite', $entity->getCompanyWebsite());
        $this->reflectField($map, 'street1', $entity->getStreet1());
        $this->reflectField($map, 'street2', $entity->getStreet2());
        $this->reflectField($map, 'city', $entity->getCity());
        $this->reflectField($map, 'countryId', $entity->getCountry(), 'id');
        $this->reflectField($map, 'stateId', $entity->getState(), 'id');
        $this->reflectField($map, 'zipCode', $entity->getZipCode());
        $this->reflectField($map, 'invoiceStreet1', $entity->getInvoiceStreet1());
        $this->reflectField($map, 'invoiceStreet2', $entity->getInvoiceStreet2());
        $this->reflectField($map, 'invoiceCity', $entity->getInvoiceCity());
        $this->reflectField($map, 'invoiceCountryId', $entity->getInvoiceCountry(), 'id');
        $this->reflectField($map, 'invoiceStateId', $entity->getInvoiceState(), 'id');
        $this->reflectField($map, 'invoiceZipCode', $entity->getInvoiceZipCode());
        $this->reflectField($map, 'invoiceAddressSameAsContact', $entity->getInvoiceAddressSameAsContact());
        $this->reflectField($map, 'note', $entity->getNote());
        $this->reflectField($map, 'sendInvoiceByPost', $entity->getSendInvoiceByPost());
        $this->reflectField($map, 'invoiceMaturityDays', $entity->getInvoiceMaturityDays());
        $this->reflectField($map, 'stopServiceDue', $entity->getStopServiceDue());
        $this->reflectField($map, 'stopServiceDueDays', $entity->getStopServiceDueDays());
        $this->reflectField($map, 'organizationId', $entity->getOrganization(), 'id');
        $this->reflectField($map, 'tax1Id', $entity->getTax1(), 'id');
        $this->reflectField($map, 'tax2Id', $entity->getTax2(), 'id');
        $this->reflectField($map, 'tax3Id', $entity->getTax3(), 'id');
        $this->reflectField($map, 'registrationDate', $entity->getRegistrationDate());
        $this->reflectField($map, 'companyContactFirstName', $entity->getCompanyContactFirstName());
        $this->reflectField($map, 'companyContactLastName', $entity->getCompanyContactLastName());
        $this->reflectField($map, 'organizationName', $entity->getOrganization()->getName());
        $this->reflectField($map, 'invitationEmailSentDate', $entity->getInvitationEmailSentDate());
        $this->reflectField($map, 'addressGpsLat', $entity->getAddressGpsLat());
        $this->reflectField($map, 'addressGpsLon', $entity->getAddressGpsLon());
        $this->reflectField($map, 'isArchived', $entity->isDeleted());
        $this->reflectField($map, 'generateProformaInvoices', $entity->getGenerateProformaInvoices());

        $this->reflectField(
            $map,
            'usesProforma',
            $entity->getGenerateProformaInvoices() ?? $this->options->get(Option::GENERATE_PROFORMA_INVOICES)
        );

        $user = $entity->getUser();

        $this->reflectField($map, 'isActive', $user->getIsActive());
        $this->reflectField($map, 'firstName', $user->getFirstName());
        $this->reflectField($map, 'lastName', $user->getLastName());
        $this->reflectField($map, 'username', $user->getUsername());
        $this->reflectField($map, 'avatarColor', $user->getAvatarColor());

        foreach ($entity->getContacts() as $contact) {
            $itemMap = new ClientContactMap();
            $this->reflectField($itemMap, 'id', $contact->getId());
            $this->reflectField($itemMap, 'clientId', $entity->getId());
            $this->reflectField($itemMap, 'email', $contact->getEmail());
            $this->reflectField($itemMap, 'phone', $contact->getPhone());
            $this->reflectField($itemMap, 'name', $contact->getName());

            $contactTypes = [];
            $isBilling = false;
            $isContact = false;
            foreach ($contact->getTypes() as $contactType) {
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
            $this->reflectField($itemMap, 'types', $contactTypes);

            $this->reflectField($itemMap, 'isBilling', $isBilling);
            $this->reflectField($itemMap, 'isContact', $isContact);

            $map->contacts[] = $itemMap;
        }

        foreach ($entity->getAttributes() as $attribute) {
            $attributeMap = new ClientAttributeMap();
            $this->reflectField($attributeMap, 'id', $attribute->getId());
            $this->reflectField($attributeMap, 'clientId', $entity->getId());
            $this->reflectField($attributeMap, 'customAttributeId', $attribute->getAttribute()->getId());
            $this->reflectField($attributeMap, 'name', $attribute->getAttribute()->getName());
            $this->reflectField($attributeMap, 'key', $attribute->getAttribute()->getKey());
            $this->reflectField($attributeMap, 'value', $attribute->getValue());

            $map->attributes[] = $attributeMap;
        }

        if ($this->permissionGrantedChecker->isGrantedSpecial(SpecialPermission::CLIENT_ACCOUNT_STANDING)) {
            $this->reflectField($map, 'accountBalance', $entity->getBalance());
            $this->reflectField($map, 'accountCredit', $entity->getAccountStandingsCredit());
            $this->reflectField($map, 'accountOutstanding', $entity->getAccountStandingsOutstanding());
            $this->reflectField($map, 'currencyCode', $entity->getCurrencyCode());
        }

        foreach ($entity->getBankAccounts() as $bankAccount) {
            $clientBankAccountMap = new ClientBankAccountMap();
            $this->reflectField($clientBankAccountMap, 'accountNumber', $bankAccount->getAccountNumber());

            $map->bankAccounts[] = $clientBankAccountMap;
        }

        foreach ($entity->getClientTags() as $clientTag) {
            $clientTagMap = new ClientTagMap();
            $this->reflectField($clientTagMap, 'id', $clientTag->getId());
            $this->reflectField($clientTagMap, 'name', $clientTag->getName());
            $this->reflectField($clientTagMap, 'colorBackground', $clientTag->getColorBackground());
            $this->reflectField($clientTagMap, 'colorText', $clientTag->getColorText());

            $map->tags[] = $clientTagMap;
        }
    }

    /**
     * {@inheritdoc}
     */
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
        ];
    }
}
