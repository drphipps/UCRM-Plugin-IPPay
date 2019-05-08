<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Entity\Import\AbstractImport;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CsvImportStructureRepository")
 */
class CsvImportStructure
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
     * @var string sha1 hash of first CSV row
     *
     * @ORM\Column(length=40, unique=true, nullable=false)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(length=1, options={"default": AbstractImport::DEFAULT_DELIMITER})
     */
    protected $csvDelimiter = AbstractImport::DEFAULT_DELIMITER;

    /**
     * @var string
     *
     * @ORM\Column(length=1, options={"default": AbstractImport::DEFAULT_ENCLOSURE})
     */
    protected $csvEnclosure = AbstractImport::DEFAULT_ENCLOSURE;

    /**
     * @var string
     *
     * @ORM\Column(length=1, options={"default": AbstractImport::DEFAULT_ESCAPE})
     */
    protected $csvEscape = AbstractImport::DEFAULT_ESCAPE;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    public function getCsvDelimiter(): string
    {
        return $this->csvDelimiter;
    }

    public function setCsvDelimiter(string $csvDelimiter): void
    {
        $this->csvDelimiter = $csvDelimiter;
    }

    public function getCsvEnclosure(): string
    {
        return $this->csvEnclosure;
    }

    public function setCsvEnclosure(string $csvEnclosure): void
    {
        $this->csvEnclosure = $csvEnclosure;
    }

    public function getCsvEscape(): string
    {
        return $this->csvEscape;
    }

    public function setCsvEscape(string $csvEscape): void
    {
        $this->csvEscape = $csvEscape;
    }
}
