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

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ClientImportRepository")
 */
class ClientImport extends AbstractImport
{
    /**
     * @var Collection|ClientImportItem[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Import\ClientImportItem", mappedBy="import", cascade={"persist"})
     * @ORM\OrderBy({"lineNumber" = "ASC"})
     */
    private $items;

    /**
     * @var ClientErrorSummary|null
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Import\ClientErrorSummary", mappedBy="import", cascade={"remove"})
     */
    private $errorSummary;

    public function __construct()
    {
        parent::__construct();

        $this->items = new ArrayCollection();
    }

    public function addItem(ClientImportItem $item): void
    {
        $this->items[] = $item;
    }

    public function removeItem(ClientImportItem $item): void
    {
        $this->items->removeElement($item);
    }

    /**
     * @return ClientImportItem[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getErrorSummary(): ?ClientErrorSummary
    {
        return $this->errorSummary;
    }

    public function setErrorSummary(?ClientErrorSummary $errorSummary): void
    {
        $this->errorSummary = $errorSummary;
    }
}
