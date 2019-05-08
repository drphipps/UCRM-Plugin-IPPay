<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SurchargeRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"deleted_at"}),
 *         @ORM\Index(columns={"name"}),
 *     }
 * )
 */
class Surcharge implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface
{
    use SoftDeleteableTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="surcharge_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     * @Assert\Length(max = 100)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $invoiceLabel;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank()
     */
    protected $price;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $taxable = false;

    /**
     * @var Tax|null
     *
     * @ORM\ManyToOne(targetEntity="Tax")
     * @ORM\JoinColumn(referencedColumnName="tax_id")
     */
    protected $tax;

    /**
     * @var Collection|ServiceSurcharge[]
     *
     * @ORM\OneToMany(targetEntity="ServiceSurcharge", mappedBy="surcharge")
     */
    protected $serviceSurcharges;

    public function __construct()
    {
        $this->serviceSurcharges = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return Surcharge
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Surcharge
     */
    public function setInvoiceLabel(?string $invoiceLabel)
    {
        $this->invoiceLabel = $invoiceLabel;

        return $this;
    }

    public function getInvoiceLabel(): ?string
    {
        return $this->invoiceLabel;
    }

    public function getInvoiceLabelForView(): ?string
    {
        if (empty($this->invoiceLabel)) {
            return $this->name;
        }

        return $this->invoiceLabel;
    }

    /**
     * @param float $price
     *
     * @return Surcharge
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param bool $taxable
     *
     * @return Surcharge
     */
    public function setTaxable($taxable)
    {
        $this->taxable = $taxable;

        return $this;
    }

    /**
     * @return bool
     */
    public function getTaxable()
    {
        return $this->taxable;
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
     * @return Collection|ServiceSurcharge[]
     */
    public function getServiceSurcharges(): Collection
    {
        return $this->serviceSurcharges;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Surcharge %s deleted',
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
            'message' => 'Surcharge %s archived',
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
            'message' => 'Surcharge %s restored',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Surcharge %s added',
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
}
