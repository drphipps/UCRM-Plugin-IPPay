<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"hash", "type"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CsvImportMappingRepository")
 */
class CsvImportMapping
{
    public const TYPE_CLIENT = 'client';
    public const TYPE_PAYMENT = 'payment';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string sha1 hash of CSV fields
     *
     * @ORM\Column(length=40, unique=true, nullable=false)
     */
    private $hash;

    /**
     * @var array
     *
     * @ORM\Column(type="json", nullable=false)
     */
    private $mapping;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     */
    private $type;

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

    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function setMapping(array $mapping): void
    {
        $this->mapping = $mapping;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
