<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait FinancialTemplateTrait
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(length=255)
     * @Assert\Length(max=255)
     * @Assert\NotNull()
     */
    protected $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    protected $createdDate;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected $errorNotificationSent = false;

    /**
     * If not NULL, it's UBNT template. The name is then used to get the template.
     *
     * @var string
     *
     * @ORM\Column(length=100, nullable=true)
     */
    protected $officialName;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    protected $isValid = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function isErrorNotificationSent(): bool
    {
        return $this->errorNotificationSent;
    }

    public function setErrorNotificationSent(bool $errorNotificationSent): void
    {
        $this->errorNotificationSent = $errorNotificationSent;
    }

    public function getOfficialName(): ?string
    {
        return $this->officialName;
    }

    public function setOfficialName(?string $officialName): void
    {
        $this->officialName = $officialName;
    }

    public function getIsValid(): bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): void
    {
        $this->isValid = $isValid;
    }
}
