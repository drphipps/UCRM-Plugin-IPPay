<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class ServiceSurcharge implements LoggableInterface, ParentLoggableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="service_surcharge_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Surcharge
     *
     * @ORM\ManyToOne(targetEntity="Surcharge", inversedBy="serviceSurcharges")
     * @ORM\JoinColumn(name="surcharge_id", referencedColumnName="surcharge_id", nullable=false)
     * @Assert\NotNull(groups={Service::VALIDATION_GROUP_DEFAULT, Service::VALIDATION_GROUP_INVOICE_PREVIEW})
     */
    private $surcharge;

    /**
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="serviceSurcharges")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="service_id", nullable=false)
     */
    private $service;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_label", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    private $invoiceLabel;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", nullable=true)
     */
    private $price;

    /**
     * @var bool
     *
     * @ORM\Column(name="taxable", type="boolean", options={"default":false})
     */
    private $taxable = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ServiceSurcharge
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
        if (! empty($this->invoiceLabel)) {
            return $this->invoiceLabel;
        }

        return $this->surcharge->getInvoiceLabelForView();
    }

    /**
     * @param bool $taxable
     *
     * @return ServiceSurcharge
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
     * @param Surcharge $surcharge
     *
     * @return ServiceSurcharge
     */
    public function setSurcharge(Surcharge $surcharge = null)
    {
        $this->surcharge = $surcharge;

        return $this;
    }

    /**
     * @return Surcharge
     */
    public function getSurcharge()
    {
        return $this->surcharge;
    }

    public function setService(?Service $service): void
    {
        $this->service = $service;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    /**
     * @param float|null $price
     *
     * @return ServiceSurcharge
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

    public function getInheritedPrice(): float
    {
        return $this->price ?? $this->surcharge->getPrice();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Surcharge %s deleted',
            'replacements' => $this->getInvoiceLabelForView(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Surcharge %s added',
            'replacements' => $this->getInvoiceLabelForView(),
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
        return $this->getService()->getClient();
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
        return $this->getService();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getInvoiceLabelForView(),
            'entity' => self::class,
        ];

        return $message;
    }
}
