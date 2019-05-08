<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\LoggableInterface;
use AppBundle\Entity\ParentLoggableInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *         "invoice_item" = "InvoiceItem",
 *         "invoice_item_service" = "InvoiceItemService",
 *         "invoice_item_product" = "InvoiceItemProduct",
 *         "invoice_item_surcharge" = "InvoiceItemSurcharge",
 *         "invoice_item_other" = "InvoiceItemOther",
 *         "invoice_item_fee" = "InvoiceItemFee"
 *     })
 *
 * @ORM\Table(name="invoice_item")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\InvoiceItemRepository")
 */
class InvoiceItem implements LoggableInterface, ParentLoggableInterface, FinancialItemInterface
{
    use FinancialItemTrait;

    /**
     * @var Invoice
     *
     * @ORM\ManyToOne(targetEntity="Invoice", inversedBy="invoiceItems")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="invoice_id", nullable=false, onDelete="CASCADE")
     */
    protected $invoice;

    public function setInvoice(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function getFinancial(): ?FinancialInterface
    {
        return $this->invoice;
    }

    /**
     * @return array
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Invoice item %s deleted',
            'replacements' => $this->getLabel(),
        ];

        return $message;
    }

    /**
     * @return array
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Invoice item %s added',
            'replacements' => $this->getLabel(),
        ];

        return $message;
    }

    /**
     * @return array
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
        return $this->getInvoice()->getClient();
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
        return $this->getInvoice();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getLabel(),
            'entity' => self::class,
        ];

        return $message;
    }
}
