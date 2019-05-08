<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Import;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ClientImportItemValidationErrorsRepository")
 */
class ClientImportItemValidationErrors implements ImportItemValidationErrorsInterface
{
    /**
     * @var string
     *
     * @ORM\Column(type="guid")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var ClientImportItem
     *
     * @ORM\OneToOne(
     *     targetEntity="AppBundle\Entity\Import\ClientImportItem",
     *     inversedBy="validationErrors"
     * )
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $clientImportItem;

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $userIdent = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $firstName = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $lastName = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $nameForView = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $username = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $companyName = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $isLead = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $addressGpsLat = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $addressGpsLon = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $companyRegistrationNumber = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $tax1 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $tax2 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $tax3 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $companyTaxId = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $companyWebsite = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $email1 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $email2 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $email3 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $emails = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $phone1 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $phone2 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $phone3 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $phones = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $street1 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $street2 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $city = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $country = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $state = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $zipCode = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoiceStreet1 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoiceStreet2 = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoiceCity = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoiceCountry = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoiceState = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoiceZipCode = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $registrationDate = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $clientNote = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $unmappedErrors = [];

    public function __construct()
    {
        // @todo use what @janprochazkacz is using when available
        $this->id = Uuid::uuid4()->toString();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getImportItem(): ImportItemInterface
    {
        return $this->clientImportItem;
    }

    public function getClientImportItem(): ClientImportItem
    {
        return $this->clientImportItem;
    }

    public function setClientImportItem(ClientImportItem $clientImportItem): void
    {
        $this->clientImportItem = $clientImportItem;
    }

    public function getUserIdent(): array
    {
        return $this->userIdent;
    }

    public function setUserIdent(array $userIdent): void
    {
        $this->userIdent = $userIdent;
    }

    public function getFirstName(): array
    {
        return $this->firstName;
    }

    public function setFirstName(array $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): array
    {
        return $this->lastName;
    }

    public function setLastName(array $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getNameForView(): array
    {
        return $this->nameForView;
    }

    public function setNameForView(array $nameForView): void
    {
        $this->nameForView = $nameForView;
    }

    public function getUsername(): array
    {
        return $this->username;
    }

    public function setUsername(array $username): void
    {
        $this->username = $username;
    }

    public function getCompanyName(): array
    {
        return $this->companyName;
    }

    public function setCompanyName(array $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getisLead(): array
    {
        return $this->isLead;
    }

    public function setIsLead(array $isLead): void
    {
        $this->isLead = $isLead;
    }

    public function getAddressGpsLat(): array
    {
        return $this->addressGpsLat;
    }

    public function setAddressGpsLat(array $addressGpsLat): void
    {
        $this->addressGpsLat = $addressGpsLat;
    }

    public function getAddressGpsLon(): array
    {
        return $this->addressGpsLon;
    }

    public function setAddressGpsLon(array $addressGpsLon): void
    {
        $this->addressGpsLon = $addressGpsLon;
    }

    public function getCompanyRegistrationNumber(): array
    {
        return $this->companyRegistrationNumber;
    }

    public function setCompanyRegistrationNumber(array $companyRegistrationNumber): void
    {
        $this->companyRegistrationNumber = $companyRegistrationNumber;
    }

    public function getTax1(): array
    {
        return $this->tax1;
    }

    public function setTax1(array $tax1): void
    {
        $this->tax1 = $tax1;
    }

    public function getTax2(): array
    {
        return $this->tax2;
    }

    public function setTax2(array $tax2): void
    {
        $this->tax2 = $tax2;
    }

    public function getTax3(): array
    {
        return $this->tax3;
    }

    public function setTax3(array $tax3): void
    {
        $this->tax3 = $tax3;
    }

    public function getCompanyTaxId(): array
    {
        return $this->companyTaxId;
    }

    public function setCompanyTaxId(array $companyTaxId): void
    {
        $this->companyTaxId = $companyTaxId;
    }

    public function getCompanyWebsite(): array
    {
        return $this->companyWebsite;
    }

    public function setCompanyWebsite(array $companyWebsite): void
    {
        $this->companyWebsite = $companyWebsite;
    }

    public function getEmail1(): array
    {
        return $this->email1;
    }

    public function setEmail1(array $email1): void
    {
        $this->email1 = $email1;
    }

    public function getEmail2(): array
    {
        return $this->email2;
    }

    public function setEmail2(array $email2): void
    {
        $this->email2 = $email2;
    }

    public function getEmail3(): array
    {
        return $this->email3;
    }

    public function setEmail3(array $email3): void
    {
        $this->email3 = $email3;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function setEmails(array $emails): void
    {
        $this->emails = $emails;
    }

    public function getPhone1(): array
    {
        return $this->phone1;
    }

    public function setPhone1(array $phone1): void
    {
        $this->phone1 = $phone1;
    }

    public function getPhone2(): array
    {
        return $this->phone2;
    }

    public function setPhone2(array $phone2): void
    {
        $this->phone2 = $phone2;
    }

    public function getPhone3(): array
    {
        return $this->phone3;
    }

    public function setPhone3(array $phone3): void
    {
        $this->phone3 = $phone3;
    }

    public function getPhones(): array
    {
        return $this->phones;
    }

    public function setPhones(array $phones): void
    {
        $this->phones = $phones;
    }

    public function getStreet1(): array
    {
        return $this->street1;
    }

    public function setStreet1(array $street1): void
    {
        $this->street1 = $street1;
    }

    public function getStreet2(): array
    {
        return $this->street2;
    }

    public function setStreet2(array $street2): void
    {
        $this->street2 = $street2;
    }

    public function getCity(): array
    {
        return $this->city;
    }

    public function setCity(array $city): void
    {
        $this->city = $city;
    }

    public function getCountry(): array
    {
        return $this->country;
    }

    public function setCountry(array $country): void
    {
        $this->country = $country;
    }

    public function getState(): array
    {
        return $this->state;
    }

    public function setState(array $state): void
    {
        $this->state = $state;
    }

    public function getZipCode(): array
    {
        return $this->zipCode;
    }

    public function setZipCode(array $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    public function getInvoiceStreet1(): array
    {
        return $this->invoiceStreet1;
    }

    public function setInvoiceStreet1(array $invoiceStreet1): void
    {
        $this->invoiceStreet1 = $invoiceStreet1;
    }

    public function getInvoiceStreet2(): array
    {
        return $this->invoiceStreet2;
    }

    public function setInvoiceStreet2(array $invoiceStreet2): void
    {
        $this->invoiceStreet2 = $invoiceStreet2;
    }

    public function getInvoiceCity(): array
    {
        return $this->invoiceCity;
    }

    public function setInvoiceCity(array $invoiceCity): void
    {
        $this->invoiceCity = $invoiceCity;
    }

    public function getInvoiceCountry(): array
    {
        return $this->invoiceCountry;
    }

    public function setInvoiceCountry(array $invoiceCountry): void
    {
        $this->invoiceCountry = $invoiceCountry;
    }

    public function getInvoiceState(): array
    {
        return $this->invoiceState;
    }

    public function setInvoiceState(array $invoiceState): void
    {
        $this->invoiceState = $invoiceState;
    }

    public function getInvoiceZipCode(): array
    {
        return $this->invoiceZipCode;
    }

    public function setInvoiceZipCode(array $invoiceZipCode): void
    {
        $this->invoiceZipCode = $invoiceZipCode;
    }

    public function getRegistrationDate(): array
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(array $registrationDate): void
    {
        $this->registrationDate = $registrationDate;
    }

    public function getClientNote(): array
    {
        return $this->clientNote;
    }

    public function setClientNote(array $clientNote): void
    {
        $this->clientNote = $clientNote;
    }

    public function getUnmappedErrors(): array
    {
        return $this->unmappedErrors;
    }

    public function setUnmappedErrors(array $unmappedErrors): void
    {
        $this->unmappedErrors = $unmappedErrors;
    }

    /**
     * @return mixed[][]
     */
    public function getErrors(): array
    {
        return array_merge(
            $this->userIdent,
            $this->firstName,
            $this->lastName,
            $this->nameForView,
            $this->username,
            $this->companyName,
            $this->isLead,
            $this->addressGpsLat,
            $this->addressGpsLon,
            $this->companyRegistrationNumber,
            $this->tax1,
            $this->tax2,
            $this->tax3,
            $this->companyTaxId,
            $this->companyWebsite,
            $this->email1,
            $this->email2,
            $this->email3,
            $this->emails,
            $this->phone1,
            $this->phone2,
            $this->phone3,
            $this->phones,
            $this->street1,
            $this->street2,
            $this->city,
            $this->country,
            $this->state,
            $this->zipCode,
            $this->invoiceStreet1,
            $this->invoiceStreet2,
            $this->invoiceCity,
            $this->invoiceCountry,
            $this->invoiceState,
            $this->invoiceZipCode,
            $this->registrationDate,
            $this->clientNote,
            $this->unmappedErrors
        );
    }

    public function hasErrors(): bool
    {
        return (bool) $this->getErrors();
    }
}
