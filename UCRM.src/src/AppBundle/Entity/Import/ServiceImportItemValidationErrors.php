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
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServiceImportItemValidationErrorsRepository")
 */
class ServiceImportItemValidationErrors implements ImportItemValidationErrorsInterface
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
     * @var ServiceImportItem
     *
     * @ORM\OneToOne(
     *     targetEntity="AppBundle\Entity\Import\ServiceImportItem",
     *     inversedBy="validationErrors"
     * )
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $serviceImportItem;

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $tariff = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoiceLabel = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $note = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $tariffPeriod = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $individualPrice = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $activeFrom = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $activeTo = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoicingStart = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoicingPeriodType = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoicingPeriodStartDay = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoicingDaysInAdvance = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoiceSeparately = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoiceUseCredit = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $invoiceApproveSendAuto = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $fccBlockId = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $contractId = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $contractType = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $contractEndDate = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $minimumContractLengthMonths = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $setupFee = [];

    /**
     * @var mixed[][]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $earlyTerminationFee = [];

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
        return $this->serviceImportItem;
    }

    public function getServiceImportItem(): ServiceImportItem
    {
        return $this->serviceImportItem;
    }

    public function setServiceImportItem(ServiceImportItem $serviceImportItem): void
    {
        $this->serviceImportItem = $serviceImportItem;
    }

    public function getTariff(): array
    {
        return $this->tariff;
    }

    public function setTariff(array $tariff): void
    {
        $this->tariff = $tariff;
    }

    public function getInvoiceLabel(): array
    {
        return $this->invoiceLabel;
    }

    public function setInvoiceLabel(array $invoiceLabel): void
    {
        $this->invoiceLabel = $invoiceLabel;
    }

    public function getNote(): array
    {
        return $this->note;
    }

    public function setNote(array $note): void
    {
        $this->note = $note;
    }

    public function getTariffPeriod(): array
    {
        return $this->tariffPeriod;
    }

    public function setTariffPeriod(array $tariffPeriod): void
    {
        $this->tariffPeriod = $tariffPeriod;
    }

    public function getIndividualPrice(): array
    {
        return $this->individualPrice;
    }

    public function setIndividualPrice(array $individualPrice): void
    {
        $this->individualPrice = $individualPrice;
    }

    public function getActiveFrom(): array
    {
        return $this->activeFrom;
    }

    public function setActiveFrom(array $activeFrom): void
    {
        $this->activeFrom = $activeFrom;
    }

    public function getActiveTo(): array
    {
        return $this->activeTo;
    }

    public function setActiveTo(array $activeTo): void
    {
        $this->activeTo = $activeTo;
    }

    public function getInvoicingStart(): array
    {
        return $this->invoicingStart;
    }

    public function setInvoicingStart(array $invoicingStart): void
    {
        $this->invoicingStart = $invoicingStart;
    }

    public function getInvoicingPeriodType(): array
    {
        return $this->invoicingPeriodType;
    }

    public function setInvoicingPeriodType(array $invoicingPeriodType): void
    {
        $this->invoicingPeriodType = $invoicingPeriodType;
    }

    public function getInvoicingPeriodStartDay(): array
    {
        return $this->invoicingPeriodStartDay;
    }

    public function setInvoicingPeriodStartDay(array $invoicingPeriodStartDay): void
    {
        $this->invoicingPeriodStartDay = $invoicingPeriodStartDay;
    }

    public function getInvoicingDaysInAdvance(): array
    {
        return $this->invoicingDaysInAdvance;
    }

    public function setInvoicingDaysInAdvance(array $invoicingDaysInAdvance): void
    {
        $this->invoicingDaysInAdvance = $invoicingDaysInAdvance;
    }

    public function getInvoiceSeparately(): array
    {
        return $this->invoiceSeparately;
    }

    public function setInvoiceSeparately(array $invoiceSeparately): void
    {
        $this->invoiceSeparately = $invoiceSeparately;
    }

    public function getInvoiceUseCredit(): array
    {
        return $this->invoiceUseCredit;
    }

    public function setInvoiceUseCredit(array $invoiceUseCredit): void
    {
        $this->invoiceUseCredit = $invoiceUseCredit;
    }

    public function getInvoiceApproveSendAuto(): array
    {
        return $this->invoiceApproveSendAuto;
    }

    public function setInvoiceApproveSendAuto(array $invoiceApproveSendAuto): void
    {
        $this->invoiceApproveSendAuto = $invoiceApproveSendAuto;
    }

    public function getFccBlockId(): array
    {
        return $this->fccBlockId;
    }

    public function setFccBlockId(array $fccBlockId): void
    {
        $this->fccBlockId = $fccBlockId;
    }

    public function getContractId(): array
    {
        return $this->contractId;
    }

    public function setContractId(array $contractId): void
    {
        $this->contractId = $contractId;
    }

    public function getContractType(): array
    {
        return $this->contractType;
    }

    public function setContractType(array $contractType): void
    {
        $this->contractType = $contractType;
    }

    public function getContractEndDate(): array
    {
        return $this->contractEndDate;
    }

    public function setContractEndDate(array $contractEndDate): void
    {
        $this->contractEndDate = $contractEndDate;
    }

    public function getMinimumContractLengthMonths(): array
    {
        return $this->minimumContractLengthMonths;
    }

    public function setMinimumContractLengthMonths(array $minimumContractLengthMonths): void
    {
        $this->minimumContractLengthMonths = $minimumContractLengthMonths;
    }

    public function getSetupFee(): array
    {
        return $this->setupFee;
    }

    public function setSetupFee(array $setupFee): void
    {
        $this->setupFee = $setupFee;
    }

    public function getEarlyTerminationFee(): array
    {
        return $this->earlyTerminationFee;
    }

    public function setEarlyTerminationFee(array $earlyTerminationFee): void
    {
        $this->earlyTerminationFee = $earlyTerminationFee;
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
            $this->tariff,
            $this->invoiceLabel,
            $this->note,
            $this->tariffPeriod,
            $this->individualPrice,
            $this->activeFrom,
            $this->activeTo,
            $this->invoicingStart,
            $this->invoicingPeriodType,
            $this->invoicingPeriodStartDay,
            $this->invoicingDaysInAdvance,
            $this->invoiceSeparately,
            $this->invoiceUseCredit,
            $this->invoiceApproveSendAuto,
            $this->fccBlockId,
            $this->contractId,
            $this->contractType,
            $this->contractEndDate,
            $this->minimumContractLengthMonths,
            $this->setupFee,
            $this->earlyTerminationFee,
            $this->tax1,
            $this->tax2,
            $this->tax3,
            $this->addressGpsLat,
            $this->addressGpsLon,
            $this->unmappedErrors
        );
    }

    public function hasErrors(): bool
    {
        return (bool) $this->getErrors();
    }
}
