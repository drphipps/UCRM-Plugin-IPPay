<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Entity\Financial\Invoice;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\InvoiceAttributeRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"invoice_id", "attribute_id"})
 *     },
 * )
 */
class InvoiceAttribute
{
    /**
     * @var int
     *
     * @ORM\Column(type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    private $id;

    /**
     * @var Invoice
     *
     * @ORM\ManyToOne(targetEntity = "AppBundle\Entity\Financial\Invoice", inversedBy = "attributes")
     * @ORM\JoinColumn(referencedColumnName="invoice_id", onDelete = "CASCADE", nullable=false)
     */
    private $invoice;

    /**
     * @var CustomAttribute
     *
     * @ORM\ManyToOne(targetEntity = "CustomAttribute")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=false)
     * @Assert\Expression(
     *     expression="this.getAttribute() and this.getAttribute().getAttributeType() === constant('AppBundle\\Entity\\CustomAttribute::ATTRIBUTE_TYPE_INVOICE')",
     *     message="Invalid custom attribute ID."
     * )
     */
    private $attribute;

    /**
     * @var string
     *
     * @ORM\Column(type = "text")
     * @Assert\NotNull()
     */
    private $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getAttribute(): ?CustomAttribute
    {
        return $this->attribute;
    }

    public function setAttribute(?CustomAttribute $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }
}
