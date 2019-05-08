<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CustomAttributeRepository")
 * @UniqueEntity(fields="key", errorPath="name")
 */
class CustomAttribute
{
    public const TYPE_STRING = 'string';

    public const ATTRIBUTE_TYPE_CLIENT = 'client';
    public const ATTRIBUTE_TYPE_INVOICE = 'invoice';

    public const ATTRIBUTE_TYPES = [
        self::ATTRIBUTE_TYPE_CLIENT => 'Client',
        self::ATTRIBUTE_TYPE_INVOICE => 'Invoice',
    ];

    public const POSSIBLE_ATTRIBUTE_TYPES = [
        self::ATTRIBUTE_TYPE_CLIENT,
        self::ATTRIBUTE_TYPE_INVOICE,
    ];

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
     * @ORM\Column(type="string", unique=true)
     * @Assert\NotBlank()
     */
    private $key;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     * @Assert\Expression(expression="this.getKey() !== ''", message="Attribute name is not valid.")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Choice(choices=CustomAttribute::POSSIBLE_ATTRIBUTE_TYPES, strict=true)
     */
    private $attributeType;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getAttributeType(): ?string
    {
        return $this->attributeType;
    }

    public function setAttributeType(?string $attributeType): void
    {
        $this->attributeType = $attributeType;
    }
}
