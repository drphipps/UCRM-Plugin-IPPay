<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CsvImportRepository")
 */
class CsvImport
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
     * @var string
     *
     * @ORM\Column(type="guid", unique=true)
     */
    private $uuid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    private $createdDate;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default":0})
     */
    private $count = 0;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default":0})
     */
    private $countSuccess = 0;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default":0})
     */
    private $countFailure = 0;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="user_id", onDelete="SET NULL")
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     */
    private $type;

    public function __construct()
    {
        // note: set only at creation - there's no ->setUuid()
        $this->uuid = Uuid::uuid4()->toString();
        $this->createdDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function getCountSuccess(): int
    {
        return $this->countSuccess;
    }

    public function setCountSuccess(int $countSuccess): void
    {
        $this->countSuccess = $countSuccess;
    }

    public function getCountFailure(): int
    {
        return $this->countFailure;
    }

    public function setCountFailure(int $countFailure): void
    {
        $this->countFailure = $countFailure;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
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
