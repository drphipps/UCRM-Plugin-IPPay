<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 * @ORM\Table(indexes={@ORM\Index(columns={"job_id", "sequence"})})
 */
class JobTask
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
     * @var Job
     *
     * @Gedmo\SortableGroup()
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="tasks")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $job;

    /**
     * @var int
     *
     * @Gedmo\SortablePosition()
     * @ORM\Column(type="integer")
     */
    private $sequence;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $label;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $closed = false;

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'closed' => $this->isClosed(),
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJob(): Job
    {
        return $this->job;
    }

    public function setJob(Job $job): void
    {
        $this->job = $job;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): void
    {
        $this->closed = $closed;
    }
}
