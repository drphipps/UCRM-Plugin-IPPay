<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TariffRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"deleted_at"}),
 *         @ORM\Index(columns={"name"}),
 *     }
 * )
 */
class Tariff implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface
{
    use SoftDeleteableTrait;

    public const TRANSMISSION_TECHNOLOGIES = [
        10 => 'Asymmetric xDSL',
        11 => 'ADSL2, ADSL2+',
        12 => 'VDSL',
        20 => 'Symmetric xDSL',
        30 => 'Other Copper Wireline (all copper-wire based technologies other than xDSL; Ethernet over copper and T-1 are examples)',
        40 => 'Cable Modem other than DOCSIS 1, 1.1, 2.0, 3.0 or 3.1',
        41 => 'Cable Modem – DOCSIS 1, 1.1 or 2.0',
        42 => 'Cable Modem – DOCSIS 3.0',
        43 => 'Cable Modem – DOCSIS 3.1',
        50 => 'Optical Carrier / Fiber to the end user (Fiber to the home or business end user, does not include “fiber to the curb”)',
        60 => 'Satellite',
        70 => 'Terrestrial Fixed Wireless',
        90 => 'Electric Power Line',
        0 => 'All Other',
    ];

    public const DEFAULT_TRANSMISSION_TECHNOLOGY = 70;

    public const FCC_SERVICE_TYPE_CONSUMER = 1;
    public const FCC_SERVICE_TYPE_BUSINESS = 2;
    public const FCC_SERVICE_TYPES = [
        self::FCC_SERVICE_TYPE_CONSUMER => 'Consumer',
        self::FCC_SERVICE_TYPE_BUSINESS => 'Business',
    ];
    public const FCC_SERVICE_POSSIBLE_TYPES = [
        self::FCC_SERVICE_TYPE_CONSUMER,
        self::FCC_SERVICE_TYPE_BUSINESS,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="tariff_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     * @Assert\Length(max = 100)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_label", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    protected $invoiceLabel;

    /**
     * Download burst size in kB.
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $downloadBurst;

    /**
     * @var float in Mbps
     *
     * @ORM\Column(name="download_speed", type="float", nullable=true)
     */
    protected $downloadSpeed;

    /**
     * Upload burst size in kB.
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $uploadBurst;

    /**
     * @var float in Mbps
     *
     * @ORM\Column(name="upload_speed", type="float", nullable=true)
     */
    protected $uploadSpeed;

    /**
     * @var int
     *
     * @ORM\Column(name="aggregation", type="integer", nullable=true)
     */
    protected $aggregation;

    /**
     * @var Collection|TariffPeriod[]
     * @ORM\OneToMany(targetEntity="TariffPeriod", mappedBy="tariff", cascade={"persist", "remove"})
     * @Assert\Valid()
     */
    protected $periods;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="tariffs")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="organization_id")
     * @Assert\NotNull()
     */
    protected $organization;

    /**
     * @var Collection|Service[]
     *
     * @ORM\OneToMany(targetEntity="Service", mappedBy="tariff")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="service_id")
     */
    protected $services;

    /**
     * @var bool
     *
     * @ORM\Column(name="taxable", type="boolean", options={"default":false})
     */
    protected $taxable = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":true})
     */
    protected $includedInFccReports = true;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $technologyOfTransmission;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Choice(choices=Tariff::FCC_SERVICE_POSSIBLE_TYPES, strict=true)
     */
    protected $fccServiceType;

    /**
     * @var float|null in Mbps
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $maximumContractualDownstreamBandwidth;

    /**
     * @var float|null in Mbps
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $maximumContractualUpstreamBandwidth;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $minimumContractLengthMonths;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $setupFee;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $earlyTerminationFee;

    /**
     * @var Tax|null
     *
     * @ORM\ManyToOne(targetEntity="Tax")
     * @ORM\JoinColumn(referencedColumnName="tax_id")
     */
    protected $tax;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $dataUsageLimit;

    public function __construct()
    {
        $this->periods = new ArrayCollection();
        $this->services = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getInvoiceLabel(): ?string
    {
        return $this->invoiceLabel;
    }

    public function getInvoiceLabelOrName(): ?string
    {
        return $this->invoiceLabel ?: $this->name;
    }

    public function setInvoiceLabel(?string $invoiceLabel): void
    {
        $this->invoiceLabel = $invoiceLabel;
    }

    public function getDownloadBurst(): ?int
    {
        return $this->downloadBurst;
    }

    public function setDownloadBurst(?int $downloadBurst): void
    {
        $this->downloadBurst = $downloadBurst;
    }

    public function getDownloadSpeed(): ?float
    {
        return $this->downloadSpeed;
    }

    public function setDownloadSpeed(?float $downloadSpeed): void
    {
        $this->downloadSpeed = $downloadSpeed;
    }

    public function getUploadBurst(): ?int
    {
        return $this->uploadBurst;
    }

    public function setUploadBurst(?int $uploadBurst): void
    {
        $this->uploadBurst = $uploadBurst;
    }

    public function getUploadSpeed(): ?float
    {
        return $this->uploadSpeed;
    }

    public function setUploadSpeed(?float $uploadSpeed): void
    {
        $this->uploadSpeed = $uploadSpeed;
    }

    /**
     * @param int $aggregation
     */
    public function setAggregation($aggregation): void
    {
        $this->aggregation = $aggregation;
    }

    /**
     * @return int
     */
    public function getAggregation()
    {
        return $this->aggregation;
    }

    /**
     * @return Collection|TariffPeriod[]
     */
    public function getPeriods(): Collection
    {
        return $this->periods->matching(
            Criteria::create()
                ->orderBy(['period' => 'ASC'])
        );
    }

    /**
     * @return Collection|TariffPeriod[]
     */
    public function getEnabledPeriods(): Collection
    {
        return $this->periods->matching(
            Criteria::create()
                ->where(Criteria::expr()->eq('enabled', true))
                ->orderBy(['period' => 'ASC'])
        );
    }

    public function addPeriod(TariffPeriod $period): void
    {
        $this->periods->add($period);
        $period->setTariff($this);
    }

    public function getPeriodByPeriod(int $period): ?TariffPeriod
    {
        foreach ($this->periods as $tariffPeriod) {
            if ($tariffPeriod->getPeriod() === $period) {
                return $tariffPeriod;
            }
        }

        return null;
    }

    public function removePeriod(TariffPeriod $period)
    {
        $this->periods->removeElement($period);
    }

    public function setOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setTaxable(bool $taxable): void
    {
        $this->taxable = $taxable;
    }

    public function getTaxable(): bool
    {
        return $this->taxable;
    }

    public function addService(Service $service): void
    {
        $this->services->add($service);
    }

    public function removeService(Service $service): void
    {
        $this->services->removeElement($service);
    }

    /**
     * @return Collection|Service[]
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function isIncludedInFccReports(): bool
    {
        return $this->includedInFccReports;
    }

    public function setIncludedInFccReports(bool $includedInFccReports): void
    {
        $this->includedInFccReports = $includedInFccReports;
    }

    public function getTechnologyOfTransmission(): ?int
    {
        return $this->technologyOfTransmission;
    }

    public function setTechnologyOfTransmission(?int $technologyOfTransmission): void
    {
        $this->technologyOfTransmission = $technologyOfTransmission;
    }

    public function getFccServiceType(): ?int
    {
        return $this->fccServiceType;
    }

    public function setFccServiceType(?int $fccServiceType): void
    {
        $this->fccServiceType = $fccServiceType;
    }

    public function getMaximumContractualDownstreamBandwidth(): ?float
    {
        return $this->maximumContractualDownstreamBandwidth;
    }

    public function setMaximumContractualDownstreamBandwidth(?float $maximumContractualDownstreamBandwidth): void
    {
        $this->maximumContractualDownstreamBandwidth = $maximumContractualDownstreamBandwidth;
    }

    public function getMaximumContractualUpstreamBandwidth(): ?float
    {
        return $this->maximumContractualUpstreamBandwidth;
    }

    public function setMaximumContractualUpstreamBandwidth(?float $maximumContractualUpstreamBandwidth): void
    {
        $this->maximumContractualUpstreamBandwidth = $maximumContractualUpstreamBandwidth;
    }

    public function getMinimumContractLengthMonths(): ?int
    {
        return $this->minimumContractLengthMonths;
    }

    public function setMinimumContractLengthMonths(?int $minimumContractLengthMonths): void
    {
        $this->minimumContractLengthMonths = $minimumContractLengthMonths;
    }

    public function getSetupFee(): ?float
    {
        return $this->setupFee;
    }

    public function setSetupFee(?float $setupFee): void
    {
        $this->setupFee = $setupFee;
    }

    public function getEarlyTerminationFee(): ?float
    {
        return $this->earlyTerminationFee;
    }

    public function setEarlyTerminationFee(?float $earlyTerminationFee): void
    {
        $this->earlyTerminationFee = $earlyTerminationFee;
    }

    public function getTax(): ?Tax
    {
        return $this->tax;
    }

    public function setTax(?Tax $tax): void
    {
        $this->tax = $tax;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Service plan %s deleted',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArchiveMessage()
    {
        $message['logMsg'] = [
            'message' => 'Service plan %s archived',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogRestoreMessage()
    {
        $message['logMsg'] = [
            'message' => 'Service plan %s restored',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Service plan %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    public function getDataUsageLimit(): ?float
    {
        return $this->dataUsageLimit;
    }

    public function setDataUsageLimit(?float $dataUsageLimit): void
    {
        $this->dataUsageLimit = $dataUsageLimit;
    }
}
