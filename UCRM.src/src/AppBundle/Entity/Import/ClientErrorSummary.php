<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity()
 */
class ClientErrorSummary
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
     * @var ClientImport
     *
     * @ORM\OneToOne(
     *     targetEntity="AppBundle\Entity\Import\ClientImport",
     *     inversedBy="errorSummary"
     * )
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $import;

    /**
     * @var Collection|ClientErrorSummaryItem[]
     *
     * @ORM\OneToMany(
     *     targetEntity="AppBundle\Entity\Import\ClientErrorSummaryItem",
     *     mappedBy="errorSummary",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    private $errorSummaryItems;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $erroneousClientCount = 0;

    /**
     * @var string[]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $missingTaxes = [];

    /**
     * @var string[]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $missingServicePlans = [];

    public function __construct()
    {
        // @todo use what @janprochazkacz is using when available
        $this->id = Uuid::uuid4()->toString();
        $this->errorSummaryItems = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getImport(): ClientImport
    {
        return $this->import;
    }

    public function setImport(ClientImport $import): void
    {
        $this->import = $import;
    }

    public function addErrorSummaryItem(ClientErrorSummaryItem $item): void
    {
        $this->errorSummaryItems[] = $item;
    }

    public function removeErrorSummaryItem(ClientErrorSummaryItem $item): void
    {
        $this->errorSummaryItems->removeElement($item);
    }

    /**
     * @return ClientErrorSummaryItem[]
     */
    public function getErrorSummaryItems(): Collection
    {
        return $this->errorSummaryItems;
    }

    /**
     * @param ClientErrorSummaryItem[] $errorSummaryItems
     */
    public function setErrorSummaryItems(Collection $errorSummaryItems): void
    {
        $this->errorSummaryItems = $errorSummaryItems;
    }

    public function getErroneousClientCount(): int
    {
        return $this->erroneousClientCount;
    }

    public function setErroneousClientCount(int $erroneousClientCount): void
    {
        $this->erroneousClientCount = $erroneousClientCount;
    }

    public function getMissingTaxes(): array
    {
        return $this->missingTaxes;
    }

    public function setMissingTaxes(array $missingTaxes): void
    {
        $this->missingTaxes = $missingTaxes;
    }

    public function getMissingServicePlans(): array
    {
        return $this->missingServicePlans;
    }

    public function setMissingServicePlans(array $missingServicePlans): void
    {
        $this->missingServicePlans = $missingServicePlans;
    }
}
