<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Import;

use AppBundle\Component\Import\Annotation\CsvColumn;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ClientImportItemRepository")
 */
class ClientImportItem implements ImportItemInterface
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
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $lineNumber;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $empty = false;

    /**
     * @var ClientImport
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Import\ClientImport", inversedBy="items")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $import;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $doImport = true;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="userIdent",
     *     label="import/Custom ID",
     *     automaticRecognition={"id", "clientid", "ident", "userident", "customid"},
     *     errorPropertyPath="userIdent"
     * )
     */
    private $userIdent;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="firstName",
     *     label="import/First name",
     *     description="You must provide either client name or company name.",
     *     automaticRecognition={"name", "firstname"},
     *     errorPropertyPath="firstName"
     * )
     */
    private $firstName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="lastName", label="import/Last name", automaticRecognition={"lastname", "surname"}, errorPropertyPath="lastName")
     */
    private $lastName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="nameForView",
     *     label="import/Name (first and last separated by space)",
     *     automaticRecognition={"fullname"},
     *     errorPropertyPath="nameForView"
     * )
     */
    private $nameForView;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="username", label="Username", automaticRecognition={"username"}, errorPropertyPath="username")
     */
    private $username;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="companyName",
     *     label="import/Company name",
     *     description="You must provide either client name or company name.",
     *     automaticRecognition={"company", "companyname"},
     *     errorPropertyPath="companyName"
     * )
     */
    private $companyName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="isLead", label="Is lead", automaticRecognition={"lead", "islead", "clientlead"}, errorPropertyPath="isLead")
     */
    private $isLead;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="addressGpsLat",
     *     label="import/Client latitude",
     *     description="Geographical latitude coordinate in Decimal Degrees",
     *     automaticRecognition={"lat", "latitude", "clientlatitude"},
     *     errorPropertyPath="addressGpsLat"
     * )
     */
    private $addressGpsLat;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="addressGpsLon",
     *     label="import/Client longitude",
     *     description="Geographical longitude coordinate in Decimal Degrees",
     *     automaticRecognition={"lon", "longitude", "clientlongitude"},
     *     errorPropertyPath="addressGpsLon"
     * )
     */
    private $addressGpsLon;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="companyRegistrationNumber",
     *     label="import/Company registration number",
     *     automaticRecognition={"registrationnumber", "companyregistrationnumber"},
     *     errorPropertyPath="companyRegistrationNumber"
     * )
     */
    private $companyRegistrationNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="tax1",
     *     label="import/Client tax 1",
     *     automaticRecognition={"tax", "clienttax", "clienttaxrate", "tax1", "clienttax1", "clienttaxrate1", "defaulttax", "taxdefault"},
     *     errorPropertyPath="tax1"
     * )
     */
    private $tax1;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="tax2",
     *     label="import/Client tax 2",
     *     automaticRecognition={"clienttax2", "clienttaxrate2", "tax2"},
     *     errorPropertyPath="tax2"
     * )
     */
    private $tax2;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="tax3",
     *     label="import/Client tax 3",
     *     automaticRecognition={"clienttax3", "clienttaxrate3", "tax3"},
     *     errorPropertyPath="tax3"
     * )
     */
    private $tax3;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="companyTaxId", label="import/Company tax ID", automaticRecognition={"taxid", "companytaxid"}, errorPropertyPath="companyTaxId")
     */
    private $companyTaxId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="companyWebsite",
     *     label="import/Company website",
     *     automaticRecognition={"web", "website", "companywebsite"},
     *     errorPropertyPath="companyWebsite"
     * )
     */
    private $companyWebsite;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="email1", label="import/Email (primary)", automaticRecognition={"email", "email1"}, errorPropertyPath="email1")
     */
    private $email1;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="email2", label="import/Email (secondary)", automaticRecognition={"email2"}, errorPropertyPath="email2")
     */
    private $email2;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="email3", label="import/Email (tertiary)", automaticRecognition={"email3"}, errorPropertyPath="email3")
     */
    private $email3;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="emails", label="import/Emails (separated by comma)", automaticRecognition={"emails"}, errorPropertyPath="emails")
     */
    private $emails;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="phone1",
     *     label="import/Phone (primary)",
     *     automaticRecognition={"phone", "telephone", "tel", "phone1"},
     *     errorPropertyPath="phone1"
     * )
     */
    private $phone1;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="phone2", label="import/Phone (secondary)", automaticRecognition={"phone2", "mobile"}, errorPropertyPath="phone2")
     */
    private $phone2;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="phone3", label="import/Phone (tertiary)", automaticRecognition={"phone3", "fax"}, errorPropertyPath="phone3")
     */
    private $phone3;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="phones", label="import/Phones (separated by comma)", automaticRecognition={"phones"}, errorPropertyPath="phones")
     */
    private $phones;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="street1", label="import/Street", automaticRecognition={"street", "street1"}, errorPropertyPath="street1")
     */
    private $street1;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="street2", label="import/Street 2", automaticRecognition={"street2"}, errorPropertyPath="street2")
     */
    private $street2;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="city", label="import/City", automaticRecognition={"city"}, errorPropertyPath="city")
     */
    private $city;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="country",
     *     label="import/Country",
     *     description="Must be one of supported countries, e.g. ""United States"".",
     *     automaticRecognition={"country"},
     *     errorPropertyPath="country"
     * )
     */
    private $country;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="state",
     *     label="import/State",
     *     description="If matched, must be valid two-letter state postal code.",
     *     automaticRecognition={"state"},
     *     errorPropertyPath="state"
     * )
     */
    private $state;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="zipCode", label="import/ZIP code", automaticRecognition={"zip", "zipcode", "postalcode"}, errorPropertyPath="zipCode")
     */
    private $zipCode;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="invoiceStreet1",
     *     label="import/Invoice street",
     *     automaticRecognition={"invoicestreet", "invoicestreet1"},
     *     errorPropertyPath="invoiceStreet1"
     * )
     */
    private $invoiceStreet1;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="invoiceStreet2", label="import/Invoice street 2", automaticRecognition={"invoicestreet2"}, errorPropertyPath="invoiceStreet2")
     */
    private $invoiceStreet2;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="invoiceCity", label="import/Invoice city", automaticRecognition={"invoicecity"}, errorPropertyPath="invoiceCity")
     */
    private $invoiceCity;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="invoiceCountry", label="import/Invoice country", automaticRecognition={"invoicecountry"}, errorPropertyPath="invoiceCountry")
     */
    private $invoiceCountry;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="invoiceState", label="import/Invoice state", automaticRecognition={"invoicestate"}, errorPropertyPath="invoiceState")
     */
    private $invoiceState;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="invoiceZipCode",
     *     label="import/Invoice ZIP code",
     *     automaticRecognition={"invoicezip", "invoicezipcode", "invoicepostalcode"},
     *     errorPropertyPath="invoiceZipCode"
     * )
     */
    private $invoiceZipCode;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="registrationDate",
     *     label="import/Registration date",
     *     description="If matched, must be date in valid format, e.g. YYYY-MM-DD.",
     *     automaticRecognition={"date", "registered", "registrationdate"},
     *     errorPropertyPath="registrationDate"
     * )
     */
    private $registrationDate;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="clientNote", label="import/Client note", automaticRecognition={"clientnote", "note"}, errorPropertyPath="clientNote")
     */
    private $clientNote;

    /**
     * @var ClientImportItemValidationErrors|null
     *
     * @ORM\OneToOne(
     *     targetEntity="AppBundle\Entity\Import\ClientImportItemValidationErrors",
     *     mappedBy="clientImportItem"
     * )
     */
    private $validationErrors;

    /**
     * True if either this or any child service item has validation errors.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $hasErrors = false;

    /**
     * True if either this or any child service item does NOT have validation errors.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $canImport = true;

    /**
     * @var Collection|ServiceImportItem[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Import\ServiceImportItem", mappedBy="importItem", cascade={"persist"})
     * @ORM\OrderBy({"lineNumber" = "ASC"})
     */
    private $serviceItems;

    public function __construct()
    {
        // @todo use what @janprochazkacz is using when available
        $this->id = Uuid::uuid4()->toString();
        $this->serviceItems = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    public function setLineNumber(int $lineNumber): void
    {
        $this->lineNumber = $lineNumber;
    }

    public function isEmpty(): bool
    {
        return $this->empty;
    }

    public function setEmpty(bool $empty): void
    {
        $this->empty = $empty;
    }

    public function getImport(): ClientImport
    {
        return $this->import;
    }

    public function setImport(ClientImport $import): void
    {
        $this->import = $import;
    }

    public function isDoImport(): bool
    {
        return $this->doImport;
    }

    public function setDoImport(bool $doImport): void
    {
        $this->doImport = $doImport;
    }

    public function getUserIdent(): ?string
    {
        return $this->userIdent;
    }

    public function setUserIdent(?string $userIdent): void
    {
        $this->userIdent = $userIdent;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getNameForView(): ?string
    {
        return $this->nameForView;
    }

    public function setNameForView(?string $nameForView): void
    {
        $this->nameForView = $nameForView;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getIsLead(): ?string
    {
        return $this->isLead;
    }

    public function setIsLead(?string $isLead): void
    {
        $this->isLead = $isLead;
    }

    public function getAddressGpsLat(): ?string
    {
        return $this->addressGpsLat;
    }

    public function setAddressGpsLat(?string $addressGpsLat): void
    {
        $this->addressGpsLat = $addressGpsLat;
    }

    public function getAddressGpsLon(): ?string
    {
        return $this->addressGpsLon;
    }

    public function setAddressGpsLon(?string $addressGpsLon): void
    {
        $this->addressGpsLon = $addressGpsLon;
    }

    public function getCompanyRegistrationNumber(): ?string
    {
        return $this->companyRegistrationNumber;
    }

    public function setCompanyRegistrationNumber(?string $companyRegistrationNumber): void
    {
        $this->companyRegistrationNumber = $companyRegistrationNumber;
    }

    public function getTax1(): ?string
    {
        return $this->tax1;
    }

    public function setTax1(?string $tax1): void
    {
        $this->tax1 = $tax1;
    }

    public function getTax2(): ?string
    {
        return $this->tax2;
    }

    public function setTax2(?string $tax2): void
    {
        $this->tax2 = $tax2;
    }

    public function getTax3(): ?string
    {
        return $this->tax3;
    }

    public function setTax3(?string $tax3): void
    {
        $this->tax3 = $tax3;
    }

    public function getCompanyTaxId(): ?string
    {
        return $this->companyTaxId;
    }

    public function setCompanyTaxId(?string $companyTaxId): void
    {
        $this->companyTaxId = $companyTaxId;
    }

    public function getCompanyWebsite(): ?string
    {
        return $this->companyWebsite;
    }

    public function setCompanyWebsite(?string $companyWebsite): void
    {
        $this->companyWebsite = $companyWebsite;
    }

    public function getEmail1(): ?string
    {
        return $this->email1;
    }

    public function setEmail1(?string $email1): void
    {
        $this->email1 = $email1;
    }

    public function getEmail2(): ?string
    {
        return $this->email2;
    }

    public function setEmail2(?string $email2): void
    {
        $this->email2 = $email2;
    }

    public function getEmail3(): ?string
    {
        return $this->email3;
    }

    public function setEmail3(?string $email3): void
    {
        $this->email3 = $email3;
    }

    public function getEmails(): ?string
    {
        return $this->emails;
    }

    public function setEmails(?string $emails): void
    {
        $this->emails = $emails;
    }

    public function getPhone1(): ?string
    {
        return $this->phone1;
    }

    public function setPhone1(?string $phone1): void
    {
        $this->phone1 = $phone1;
    }

    public function getPhone2(): ?string
    {
        return $this->phone2;
    }

    public function setPhone2(?string $phone2): void
    {
        $this->phone2 = $phone2;
    }

    public function getPhone3(): ?string
    {
        return $this->phone3;
    }

    public function setPhone3(?string $phone3): void
    {
        $this->phone3 = $phone3;
    }

    public function getPhones(): ?string
    {
        return $this->phones;
    }

    public function setPhones(?string $phones): void
    {
        $this->phones = $phones;
    }

    public function getStreet1(): ?string
    {
        return $this->street1;
    }

    public function setStreet1(?string $street1): void
    {
        $this->street1 = $street1;
    }

    public function getStreet2(): ?string
    {
        return $this->street2;
    }

    public function setStreet2(?string $street2): void
    {
        $this->street2 = $street2;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    public function getInvoiceStreet1(): ?string
    {
        return $this->invoiceStreet1;
    }

    public function setInvoiceStreet1(?string $invoiceStreet1): void
    {
        $this->invoiceStreet1 = $invoiceStreet1;
    }

    public function getInvoiceStreet2(): ?string
    {
        return $this->invoiceStreet2;
    }

    public function setInvoiceStreet2(?string $invoiceStreet2): void
    {
        $this->invoiceStreet2 = $invoiceStreet2;
    }

    public function getInvoiceCity(): ?string
    {
        return $this->invoiceCity;
    }

    public function setInvoiceCity(?string $invoiceCity): void
    {
        $this->invoiceCity = $invoiceCity;
    }

    public function getInvoiceCountry(): ?string
    {
        return $this->invoiceCountry;
    }

    public function setInvoiceCountry(?string $invoiceCountry): void
    {
        $this->invoiceCountry = $invoiceCountry;
    }

    public function getInvoiceState(): ?string
    {
        return $this->invoiceState;
    }

    public function setInvoiceState(?string $invoiceState): void
    {
        $this->invoiceState = $invoiceState;
    }

    public function getInvoiceZipCode(): ?string
    {
        return $this->invoiceZipCode;
    }

    public function setInvoiceZipCode(?string $invoiceZipCode): void
    {
        $this->invoiceZipCode = $invoiceZipCode;
    }

    public function getRegistrationDate(): ?string
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(?string $registrationDate): void
    {
        $this->registrationDate = $registrationDate;
    }

    public function getClientNote(): ?string
    {
        return $this->clientNote;
    }

    public function setClientNote(?string $clientNote): void
    {
        $this->clientNote = $clientNote;
    }

    public function getValidationErrors(): ?ClientImportItemValidationErrors
    {
        return $this->validationErrors;
    }

    public function setValidationErrors(?ClientImportItemValidationErrors $validationErrors): void
    {
        $this->validationErrors = $validationErrors;
    }

    public function hasErrors(): bool
    {
        return $this->hasErrors;
    }

    public function setHasErrors(bool $hasErrors): void
    {
        $this->hasErrors = $hasErrors;
    }

    public function isCanImport(): bool
    {
        return $this->canImport;
    }

    public function setCanImport(bool $canImport): void
    {
        $this->canImport = $canImport;
    }

    public function addServiceItem(ServiceImportItem $serviceItem): void
    {
        $this->serviceItems[] = $serviceItem;
    }

    public function removeServiceItem(ServiceImportItem $serviceItem): void
    {
        $this->serviceItems->removeElement($serviceItem);
    }

    /**
     * @return ServiceImportItem[]
     */
    public function getServiceItems(): Collection
    {
        return $this->serviceItems;
    }

    /**
     * @return string[]
     */
    public function getAddress(): array
    {
        return array_filter(
            [
                $this->getStreet1(),
                $this->getStreet2(),
                $this->getCity(),
                $this->getZipCode(),
                $this->getState(),
                $this->getCountry(),
            ]
        );
    }

    /**
     * @return string[]
     */
    public function getInvoiceAddress(): array
    {
        return array_filter(
            [
                $this->getInvoiceStreet1(),
                $this->getInvoiceStreet2(),
                $this->getInvoiceCity(),
                $this->getInvoiceZipCode(),
                $this->getInvoiceState(),
                $this->getInvoiceCountry(),
            ]
        );
    }

    public function getErrorSummaryType(): string
    {
        return ClientErrorSummaryItem::TYPE_CLIENT;
    }
}
