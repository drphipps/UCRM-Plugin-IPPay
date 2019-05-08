<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Entity\Financial\Invoice;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DraftGenerationItemRepository")
 */
class DraftGenerationItem
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var DraftGeneration
     *
     * @ORM\ManyToOne(targetEntity="DraftGeneration", inversedBy="items")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $draftGeneration;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $draft = false;

    /**
     * @var Invoice
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\Invoice")
     * @ORM\JoinColumn(referencedColumnName="invoice_id", nullable=false, onDelete="CASCADE")
     */
    private $invoice;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDraftGeneration(): DraftGeneration
    {
        return $this->draftGeneration;
    }

    public function setDraftGeneration(DraftGeneration $draftGeneration): void
    {
        $this->draftGeneration = $draftGeneration;
    }

    public function isDraft(): bool
    {
        return $this->draft;
    }

    public function setDraft(bool $draft): void
    {
        $this->draft = $draft;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }
}
