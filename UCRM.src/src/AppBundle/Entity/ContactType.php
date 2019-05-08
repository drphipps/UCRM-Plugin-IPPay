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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContactTypeRepository")
 * @UniqueEntity("name", message="This contact type name is already used.")
 */
class ContactType
{
    public const IS_BILLING = 1;
    public const IS_CONTACT = 2;

    public const CONTACT_TYPE_MAX_SYSTEM_ID = 1000;

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
     * @ORM\Column(length=255, unique=true)
     * @Assert\NotNull()
     * @Assert\Length(max = 255)
     */
    protected $name;

    /**
     * @var Collection|ClientContact[]
     *
     * @ORM\ManyToMany(targetEntity="ClientContact", mappedBy="types", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(onDelete="CASCADE")}
     * )
     */
    protected $clientContacts;

    public function __construct()
    {
        $this->clientContacts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection|ClientContact[]
     */
    public function getClientContacts(): Collection
    {
        return $this->clientContacts;
    }

    public function addClientContact(ClientContact $clientContact): void
    {
        if ($this->clientContacts->contains($clientContact)) {
            return;
        }

        $this->clientContacts->add($clientContact);
    }

    public function removeClientContact(ClientContact $clientContact): void
    {
        if (! $this->clientContacts->contains($clientContact)) {
            return;
        }

        $this->clientContacts->removeElement($clientContact);
    }
}
