<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Cache(region="region_lists")
 */
class Country implements ParentLoggableInterface
{
    public const COUNTRY_UNITED_STATES = 249;

    public const FCC_COUNTRIES = [
        249, // United States
        248, // United States Minor Outlying Islands
        27, // American Samoa
        108, // Guam
        165, // Northern Mariana Islands
        198, // Puerto Rico
        256, // U.S. Virgin Islands
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="country_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     * @Assert\NotBlank()
     * @Assert\Length(max = 50)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=2, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max = 2)
     */
    private $code;

    /**
     * @var Collection|State[]
     *
     * @ORM\OneToMany(targetEntity="State", mappedBy="country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="country_id", nullable=true);
     */
    private $states;

    public function __construct()
    {
        $this->states = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return Country
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $code
     *
     * @return Country
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function addState(State $state)
    {
        $this->states->add($state);
        $state->setCountry($this);
    }

    /**
     * @return Collection|State[]
     */
    public function getStates()
    {
        return $this->states;
    }

    public function removeState(State $state)
    {
        $this->states->removeElement($state);
    }

    public function isFccCountry(): bool
    {
        return in_array($this->getId(), self::FCC_COUNTRIES, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }
}
