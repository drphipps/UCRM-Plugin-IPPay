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
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TariffPeriodRepository")
 */
class TariffPeriod implements LoggableInterface, ParentLoggableInterface
{
    const PERIODS = [1, 2, 3, 6, 12, 24];

    const PERIOD_REPLACE_STRING = [
        1 => '1 month',
        2 => '2 months',
        3 => '3 months',
        6 => '6 months',
        12 => '1 year',
        24 => '2 years',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="period_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var float|null
     *
     * @ORM\Column(name="price", type="float", nullable=true)
     * @Assert\Expression(
     *     expression="! this.isEnabled() or this.hasPrice()",
     *     message="This value should not be blank."
     * )
     */
    private $price;

    /**
     * @var int
     *
     * @ORM\Column(name="period", type="integer")
     * @Assert\Choice(choices=TariffPeriod::PERIODS, strict=true)
     */
    private $period;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", options={"default":false})
     */
    private $enabled = false;

    /**
     * @var Tariff
     *
     * @ORM\ManyToOne(targetEntity="Tariff", inversedBy="periods")
     * @ORM\JoinColumn(name="tariff_id", referencedColumnName="tariff_id", nullable=false)
     */
    private $tariff;

    /**
     * @var Collection|Service[]
     *
     * @ORM\OneToMany(targetEntity="Service", mappedBy="tariffPeriod")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="service_id")
     */
    private $services;

    public function __construct(int $period, bool $enabled = true)
    {
        $this->period = $period;
        $this->enabled = $enabled;

        $this->services = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setPrice(?float $price): void
    {
        $this->price = $price;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function hasPrice(): bool
    {
        return null !== $this->price && $this->price >= 0;
    }

    public function setPeriod(int $period): void
    {
        $this->period = $period;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getTariff(): Tariff
    {
        return $this->tariff;
    }

    public function setTariff(Tariff $tariff): void
    {
        $this->tariff = $tariff;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): void
    {
        $this->enabled = (bool) $enabled;
    }

    public function addService(Service $service): void
    {
        $this->services[] = $service;
    }

    public function removeService(Service $service): void
    {
        $this->services->removeElement($service);
    }

    /**
     * @return Service[]
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Service plan period %s deleted',
            'replacements' => $this->getPeriod(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Service plan period %s added',
            'replacements' => $this->getPeriod(),
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
        return $this->getTariff();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getPrice(),
            'entity' => self::class,
        ];

        return $message;
    }
}
