<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CurrencyRepository")
 * @ORM\Cache(region="region_lists")
 */
class Currency implements ParentLoggableInterface
{
    const DEFAULT_ID = 33; // USD

    /**
     * @var int
     *
     * @ORM\Column(name="currency_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     * @Assert\Length(max = 50)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=3, unique=true)
     * @Assert\Length(max = 3)
     * @Assert\NotBlank()
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\Length(max = 5)
     */
    private $symbol;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $obsolete = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function isObsolete(): bool
    {
        return $this->obsolete;
    }

    public function setObsolete(bool $obsolete): void
    {
        $this->obsolete = $obsolete;
    }

    public function getCurrencyLabel(): string
    {
        return sprintf('%s (%s)', $this->code, $this->symbol);
    }

    public function getLogUpdateMessage(): array
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    public function getFractionDigits(): int
    {
        return Intl::getCurrencyBundle()->getFractionDigits($this->code) ?? 2;
    }
}
