<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Entity;

use AppBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="SchedulingBundle\Repository\JobCommentRepository")
 */
class JobComment
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
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User")
     * @ORM\JoinColumn(referencedColumnName="user_id", nullable=true, onDelete="SET NULL")
     */
    private $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    private $createdDate;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull()
     */
    private $message;

    /**
     * @var Job
     *
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="comments")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    private $job;

    public function __construct()
    {
        $this->createdDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(Job $job): void
    {
        $this->job = $job;
    }
}
