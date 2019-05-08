<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ClientAttributeRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"client_id", "attribute_id"})
 *     },
 * )
 */
class ClientAttribute implements LoggableInterface, ParentLoggableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    private $id;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity = "Client", inversedBy = "attributes")
     * @ORM\JoinColumn(referencedColumnName="client_id", onDelete = "CASCADE", nullable=false)
     */
    private $client;

    /**
     * @var CustomAttribute
     *
     * @ORM\ManyToOne(targetEntity = "CustomAttribute")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=false)
     * @Assert\Expression(
     *     expression="this.getAttribute() and this.getAttribute().getAttributeType() === constant('AppBundle\\Entity\\CustomAttribute::ATTRIBUTE_TYPE_CLIENT')",
     *     message="Invalid custom attribute ID."
     * )
     */
    private $attribute;

    /**
     * @var string
     *
     * @ORM\Column(type = "text")
     * @Assert\NotNull()
     */
    private $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getAttribute(): ?CustomAttribute
    {
        return $this->attribute;
    }

    public function setAttribute(?CustomAttribute $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    private function getValuesForLog(): string
    {
        return implode(
            ', ',
            array_filter(
                [$this->getAttribute()->getName(), $this->getValue()]
            )
        );
    }

    public function getLogInsertMessage(): array
    {
        return [
            'logMsg' => [
                'message' => 'Client attribute %s added',
                'replacements' => $this->getValuesForLog(),
            ],
        ];
    }

    public function getLogDeleteMessage(): array
    {
        return [
            'logMsg' => [
                'message' => 'Client attribute %s deleted',
                'replacements' => $this->getId(),
            ],
        ];
    }

    public function getLogIgnoredColumns(): array
    {
        return [];
    }

    public function getLogClient(): Client
    {
        return $this->getClient();
    }

    public function getLogSite(): ?Site
    {
        return null;
    }

    public function getLogParentEntity()
    {
        return null;
    }

    public function getLogUpdateMessage(): array
    {
        return [
            'logMsg' => [
                'id' => $this->getId(),
                'message' => $this->getValuesForLog(),
                'entity' => self::class,
            ],
        ];
    }
}
