<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"deleted_at"}),
 *         @ORM\Index(columns={"name"}),
 *     }
 * )
 */
class Product implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface
{
    use SoftDeleteableTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="product_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=500)
     * @Assert\Length(max = 500)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_label", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $invoiceLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="unit", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $unit;

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
     * @ORM\Column(name="taxable", type="boolean", options={"default":false})
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return Product
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
     * @param float $price
     *
     * @return Product
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
     * @param string $invoiceLabel
     *
     * @return Product
     */
    public function setInvoiceLabel($invoiceLabel)
    {
        $this->invoiceLabel = $invoiceLabel;

        return $this;
    }

    /**
     * @return string
     */
    public function getInvoiceLabel()
    {
        if (empty($this->invoiceLabel)) {
            return $this->name;
        }

        return $this->invoiceLabel;
    }

    /**
     * @param string $unit
     *
     * @return Product
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param bool $taxable
     *
     * @return Product
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

    /**
     * @return bool
     */
    public function isTaxable()
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
     * {@inheritdoc}
     */
    public function getLogDeleteMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Product %s deleted',
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
            'message' => 'Product %s archived',
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
            'message' => 'Product %s restored',
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
            'message' => 'Product %s added',
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
