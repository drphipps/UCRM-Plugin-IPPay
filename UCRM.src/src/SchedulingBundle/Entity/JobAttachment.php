<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class JobAttachment
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
     * @var string
     *
     * @ORM\Column(length=50)
     * @Assert\Length(max = 50)
     * @Assert\NotNull()
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(length=255)
     * @Assert\Length(max = 255)
     * @Assert\NotNull()
     */
    private $originalFilename;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull()
     */
    private $mimeType;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @Assert\NotNull()
     */
    private $size;

    /**
     * @var Job
     *
     * @ORM\ManyToOne(targetEntity="SchedulingBundle\Entity\Job", inversedBy="attachments")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $job;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getJob(): Job
    {
        return $this->job;
    }

    public function setJob(Job $job): void
    {
        $this->job = $job;
    }
}
