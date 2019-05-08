<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Import;

use AppBundle\Component\Import\Annotation\CsvColumn;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServiceImportItemRepository")
 */
class ServiceImportItem implements ImportItemInterface
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
     * @var ClientImportItem
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Import\ClientImportItem", inversedBy="serviceItems")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $importItem;

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
     *     csvMappingField="servicePlan",
     *     label="import/Service plan",
     *     automaticRecognition={"service", "tariff", "serviceplan", "plan"},
     *     errorPropertyPath="tariff"
     * )
     */
    private $tariff;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceInvoiceLabel",
     *     label="import/Service invoice label",
     *     automaticRecognition={"serviceinvoicelabel", "servicecustomlabel"},
     *     errorPropertyPath="invoiceLabel"
     * )
     */
    private $invoiceLabel;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="serviceNote", label="import/Service note", automaticRecognition={"servicenote"}, errorPropertyPath="note")
     */
    private $note;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceTariffPeriod",
     *     label="import/Service period in months",
     *     automaticRecognition={"period", "serviceperiod", "planperiod", "tariffperiod"},
     *     errorPropertyPath="tariffPeriod"
     * )
     */
    private $tariffPeriod;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceIndividualPrice",
     *     label="import/Service individual price",
     *     automaticRecognition={"price", "serviceprice", "serviceindividualprice", "individualprice", "planprice", "tariffprice"},
     *     errorPropertyPath="individualPrice"
     * )
     */
    private $individualPrice;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceActiveFrom",
     *     label="import/Service active from",
     *     automaticRecognition={"serviceactivefrom"},
     *     errorPropertyPath="activeFrom"
     * )
     */
    private $activeFrom;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="serviceActiveTo", label="import/Service active to", automaticRecognition={"serviceactiveto"}, errorPropertyPath="activeTo")
     */
    private $activeTo;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceInvoicingStart",
     *     label="import/Service invoicing from",
     *     automaticRecognition={"serviceinvoicingfrom", "serviceinvoicingstarts", "serviceinvoicingstart", "serviceinvoicingbegin"},
     *     errorPropertyPath="invoicingStart"
     * )
     */
    private $invoicingStart;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceInvoicingPeriodType",
     *     label="import/Service invoicing type",
     *     automaticRecognition={"serviceinvoicing", "serviceinvoicingtype", "serviceinvoicingperiodtype", "serviceinvoicingtypeperiod"},
     *     errorPropertyPath="invoicingPeriodType"
     * )
     */
    private $invoicingPeriodType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceInvoicingPeriodStartDay",
     *     label="import/Service invoicing period start day",
     *     automaticRecognition={"serviceinvoicingperiodstartday"},
     *     errorPropertyPath="invoicingPeriodStartDay"
     * )
     */
    private $invoicingPeriodStartDay;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceInvoicingDaysInAdvance",
     *     label="import/Service create invoice X days in advance",
     *     automaticRecognition={"servicecreateinvoicexdaysinadvance", "serviceinvoicingdaysinadvance"},
     *     errorPropertyPath="nextInvoicingDayAdjustment"
     * )
     */
    private $invoicingDaysInAdvance;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceInvoiceSeparately",
     *     label="import/Service invoice separately",
     *     automaticRecognition={"serviceinvoiceseparately"},
     *     errorPropertyPath="invoiceSeparately"
     * )
     */
    private $invoiceSeparately;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceInvoiceUseCredit",
     *     label="import/Service invoice use credit automatically",
     *     automaticRecognition={"serviceinvoiceusecredit", "serviceinvoiceusecreditautomatically"},
     *     errorPropertyPath="invoiceUseCredit"
     * )
     */
    private $invoiceUseCredit;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceInvoiceApproveSendAuto",
     *     label="import/Service invoice approve and send automatically",
     *     automaticRecognition={"serviceinvoiceapproveandsendautomatically", "serviceinvoiceapprovesendauto"},
     *     errorPropertyPath="invoiceApproveSendAuto"
     * )
     */
    private $invoiceApproveSendAuto;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceFccBlockId",
     *     label="import/Service Census Block GEOID",
     *     automaticRecognition={"servicegeoid", "servicecensusgeoid", "servicecensus", "serviceblockgeoid", "servicecensusblockgeoid", "servicefccblockid"},
     *     errorPropertyPath="fccBlockId"
     * )
     */
    private $fccBlockId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceContractId",
     *     label="import/Service contract ID",
     *     automaticRecognition={"servicecontractid"},
     *     errorPropertyPath="contractId"
     * )
     */
    private $contractId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceContractType",
     *     label="import/Service contract type (open/closed)",
     *     automaticRecognition={"servicecontracttype"},
     *     errorPropertyPath="contractType"
     * )
     */
    private $contractType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceContractEndDate",
     *     label="import/Service contract end date",
     *     automaticRecognition={"servicecontractenddate"},
     *     errorPropertyPath="contractEndDate"
     * )
     */
    private $contractEndDate;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceMinimumContractLengthMonths",
     *     label="import/Service minimum contract length (months)",
     *     automaticRecognition={"serviceminimumcontractlength"},
     *     errorPropertyPath="minimumContractLengthMonths"
     * )
     */
    private $minimumContractLengthMonths;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(csvMappingField="serviceSetupFee", label="import/Service setup fee", automaticRecognition={"servicesetupfee"}, errorPropertyPath="setupFee")
     */
    private $setupFee;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceEarlyTerminationFee",
     *     label="import/Service early termination fee",
     *     automaticRecognition={"serviceearlyterminationfee"},
     *     errorPropertyPath="earlyTerminationFee"
     * )
     */
    private $earlyTerminationFee;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceTax1",
     *     label="import/Service tax 1",
     *     automaticRecognition={"servicetax", "servicetaxrate", "servicetax1", "servicetaxrate1"},
     *     errorPropertyPath="tax1"
     * )
     */
    private $tax1;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceTax2",
     *     label="import/Service tax 2",
     *     automaticRecognition={"servicetax2", "servicetaxrate2"},
     *     errorPropertyPath="tax2"
     * )
     */
    private $tax2;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceTax3",
     *     label="import/Service tax 3",
     *     automaticRecognition={"servicetax3", "servicetaxrate3"},
     *     errorPropertyPath="tax3"
     * )
     */
    private $tax3;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceAddressGpsLat",
     *     label="import/Service latitude",
     *     automaticRecognition={"latitudeservice", "servicelatitude"},
     *     errorPropertyPath="addressGpsLat"
     * )
     */
    private $addressGpsLat;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @CsvColumn(
     *     csvMappingField="serviceAddressGpsLon",
     *     label="import/Service longitude",
     *     automaticRecognition={"longitudeservice", "servicelongitude"},
     *     errorPropertyPath="addressGpsLon"
     * )
     */
    private $addressGpsLon;

    /**
     * @var ServiceImportItemValidationErrors|null
     *
     * @ORM\OneToOne(
     *     targetEntity="AppBundle\Entity\Import\ServiceImportItemValidationErrors",
     *     mappedBy="serviceImportItem"
     * )
     */
    private $validationErrors;

    public function __construct()
    {
        // @todo use what @janprochazkacz is using when available
        $this->id = Uuid::uuid4()->toString();
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

    public function getImportItem(): ClientImportItem
    {
        return $this->importItem;
    }

    public function setImportItem(ClientImportItem $importItem): void
    {
        $this->importItem = $importItem;
    }

    public function isDoImport(): bool
    {
        return $this->doImport;
    }

    public function setDoImport(bool $doImport): void
    {
        $this->doImport = $doImport;
    }

    public function getTariff(): ?string
    {
        return $this->tariff;
    }

    public function setTariff(?string $tariff): void
    {
        $this->tariff = $tariff;
    }

    public function getInvoiceLabel(): ?string
    {
        return $this->invoiceLabel;
    }

    public function setInvoiceLabel(?string $invoiceLabel): void
    {
        $this->invoiceLabel = $invoiceLabel;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getTariffPeriod(): ?string
    {
        return $this->tariffPeriod;
    }

    public function setTariffPeriod(?string $tariffPeriod): void
    {
        $this->tariffPeriod = $tariffPeriod;
    }

    public function getIndividualPrice(): ?string
    {
        return $this->individualPrice;
    }

    public function setIndividualPrice(?string $individualPrice): void
    {
        $this->individualPrice = $individualPrice;
    }

    public function getActiveFrom(): ?string
    {
        return $this->activeFrom;
    }

    public function setActiveFrom(?string $activeFrom): void
    {
        $this->activeFrom = $activeFrom;
    }

    public function getActiveTo(): ?string
    {
        return $this->activeTo;
    }

    public function setActiveTo(?string $activeTo): void
    {
        $this->activeTo = $activeTo;
    }

    public function getInvoicingStart(): ?string
    {
        return $this->invoicingStart;
    }

    public function setInvoicingStart(?string $invoicingStart): void
    {
        $this->invoicingStart = $invoicingStart;
    }

    public function getInvoicingPeriodType(): ?string
    {
        return $this->invoicingPeriodType;
    }

    public function setInvoicingPeriodType(?string $invoicingPeriodType): void
    {
        $this->invoicingPeriodType = $invoicingPeriodType;
    }

    public function getInvoicingPeriodStartDay(): ?string
    {
        return $this->invoicingPeriodStartDay;
    }

    public function setInvoicingPeriodStartDay(?string $invoicingPeriodStartDay): void
    {
        $this->invoicingPeriodStartDay = $invoicingPeriodStartDay;
    }

    public function getInvoicingDaysInAdvance(): ?string
    {
        return $this->invoicingDaysInAdvance;
    }

    public function setInvoicingDaysInAdvance(?string $invoicingDaysInAdvance): void
    {
        $this->invoicingDaysInAdvance = $invoicingDaysInAdvance;
    }

    public function getInvoiceSeparately(): ?string
    {
        return $this->invoiceSeparately;
    }

    public function setInvoiceSeparately(?string $invoiceSeparately): void
    {
        $this->invoiceSeparately = $invoiceSeparately;
    }

    public function getInvoiceUseCredit(): ?string
    {
        return $this->invoiceUseCredit;
    }

    public function setInvoiceUseCredit(?string $invoiceUseCredit): void
    {
        $this->invoiceUseCredit = $invoiceUseCredit;
    }

    public function getInvoiceApproveSendAuto(): ?string
    {
        return $this->invoiceApproveSendAuto;
    }

    public function setInvoiceApproveSendAuto(?string $invoiceApproveSendAuto): void
    {
        $this->invoiceApproveSendAuto = $invoiceApproveSendAuto;
    }

    public function getFccBlockId(): ?string
    {
        return $this->fccBlockId;
    }

    public function setFccBlockId(?string $fccBlockId): void
    {
        $this->fccBlockId = $fccBlockId;
    }

    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    public function setContractId(?string $contractId): void
    {
        $this->contractId = $contractId;
    }

    public function getContractType(): ?string
    {
        return $this->contractType;
    }

    public function setContractType(?string $contractType): void
    {
        $this->contractType = $contractType;
    }

    public function getContractEndDate(): ?string
    {
        return $this->contractEndDate;
    }

    public function setContractEndDate(?string $contractEndDate): void
    {
        $this->contractEndDate = $contractEndDate;
    }

    public function getMinimumContractLengthMonths(): ?string
    {
        return $this->minimumContractLengthMonths;
    }

    public function setMinimumContractLengthMonths(?string $minimumContractLengthMonths): void
    {
        $this->minimumContractLengthMonths = $minimumContractLengthMonths;
    }

    public function getSetupFee(): ?string
    {
        return $this->setupFee;
    }

    public function setSetupFee(?string $setupFee): void
    {
        $this->setupFee = $setupFee;
    }

    public function getEarlyTerminationFee(): ?string
    {
        return $this->earlyTerminationFee;
    }

    public function setEarlyTerminationFee(?string $earlyTerminationFee): void
    {
        $this->earlyTerminationFee = $earlyTerminationFee;
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

    public function getValidationErrors(): ?ServiceImportItemValidationErrors
    {
        return $this->validationErrors;
    }

    public function setValidationErrors(?ServiceImportItemValidationErrors $validationErrors): void
    {
        $this->validationErrors = $validationErrors;
    }

    public function getErrorSummaryType(): string
    {
        return ClientErrorSummaryItem::TYPE_SERVICE;
    }
}
