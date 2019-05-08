<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ClientContactRepository")
 */
class ClientContact implements LoggableInterface, ParentLoggableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="client_contact_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="contacts")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=false, onDelete="CASCADE")
     */
    protected $client;

    /**
     * @var string
     *
     * @ORM\Column(type="citext", length=320, nullable=true)
     * @Assert\Length(max = 320, groups={"Default", "CsvClientContact"})
     * @Assert\Email(
     *     groups={"Default", "CsvClientContact"},
     *     strict=true
     * )
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max = 50, groups={"Default", "CsvClientContact"})
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $name;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $isLogin = false;

    /**
     * @var Collection|ContactType[]
     *
     * @ORM\ManyToMany(targetEntity="ContactType", inversedBy="clientContacts", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(referencedColumnName="client_contact_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"name" = "ASC"})
     */
    protected $types;

    public function __construct()
    {
        $this->types = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setEmail(?string $email = null): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setPhone(string $phone = null): void
    {
        $this->phone = $phone;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setIsLogin(bool $isLogin): void
    {
        $this->isLogin = $isLogin;
    }

    public function getIsLogin(): bool
    {
        return $this->isLogin;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return Collection|ContactType[]
     */
    public function getTypes(): Collection
    {
        return $this->types;
    }

    public function addType(ContactType $contactType): void
    {
        if ($this->types->contains($contactType)) {
            return;
        }

        $this->types->add($contactType);
    }

    public function removeType(ContactType $contactType): void
    {
        if (! $this->types->contains($contactType)) {
            return;
        }

        $this->types->removeElement($contactType);
    }

    private function getValuesForLog(): string
    {
        return implode(
            ', ',
            array_filter(
                [$this->getEmail(), $this->getPhone(), $this->getName(), $this->getIsLogin() ? 'true' : 'false']
            )
        );
    }

    public function getLogInsertMessage(): array
    {
        return [
            'logMsg' => [
                'message' => 'Client contact %s added',
                'replacements' => $this->getValuesForLog(),
            ],
        ];
    }

    public function getLogDeleteMessage(): array
    {
        return [
            'logMsg' => [
                'message' => 'Client contact %s deleted',
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
