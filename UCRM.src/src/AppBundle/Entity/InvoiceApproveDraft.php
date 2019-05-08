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
 * @ORM\Entity()
 *
 * @deprecated @todo Can be safely deleted in the future when everyone is on 2.3.0.
 * @see \AppBundle\Command\Migration\MoveQueuesToRabbitCommand
 */
class InvoiceApproveDraft
{
    /**
     * @var Invoice
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\Invoice")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="invoice_id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    protected $invoice;

    public function setInvoice(?Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }
}
