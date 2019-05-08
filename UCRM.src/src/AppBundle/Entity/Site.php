<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Location\Coordinate;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"deleted_at"}),
 *         @ORM\Index(columns={"name"}),
 *     }
 * )
 */
class Site implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface
{
    use SoftDeleteableTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="site_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     * @Assert\Length(max = 100)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=200)
     * @Assert\Length(max = 200)
     * @Assert\NotBlank()
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="gps_lat", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     * @Assert\Range(
     *     min = -90,
     *     max = 90
     * )
     */
    private $gpsLat;

    /**
     * @var string
     *
     * @ORM\Column(name="gps_lon", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     * @Assert\Range(
     *     min = -180,
     *     max = 180
     * )
     */
    private $gpsLon;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_info", type="text", nullable=true)
     */
    private $contactInfo;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;

    /**
     * @var Collection|Device[]
     *
     * @ORM\OneToMany(targetEntity="Device", mappedBy="site")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="site_id")
     */
    private $devices;

    public function __construct()
    {
        $this->devices = new ArrayCollection();
    }

    /**
     * @return $this
     */
    public function addDevice(Device $device)
    {
        $this->devices->add($device);

        return $this;
    }

    /**
     * @deprecated use getNotDeletedDevices instead
     *
     * @return Collection|Device[]
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * @return Collection|Device[]
     */
    public function getNotDeletedDevices()
    {
        return $this->devices->matching(
            Criteria::create()->where(Criteria::expr()->isNull('deletedAt'))
        );
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
     * @return Site
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
     * @param string $address
     *
     * @return Site
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $contactInfo
     *
     * @return Site
     */
    public function setContactInfo($contactInfo)
    {
        $this->contactInfo = $contactInfo;

        return $this;
    }

    /**
     * @return string
     */
    public function getContactInfo()
    {
        return $this->contactInfo;
    }

    /**
     * @param string $notes
     *
     * @return Site
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $gpsLat
     *
     * @return Site
     */
    public function setGpsLat($gpsLat)
    {
        $this->gpsLat = $gpsLat;

        return $this;
    }

    /**
     * @return string
     */
    public function getGpsLat()
    {
        return $this->gpsLat;
    }

    /**
     * @param string $gpsLon
     *
     * @return Site
     */
    public function setGpsLon($gpsLon)
    {
        $this->gpsLon = $gpsLon;

        return $this;
    }

    /**
     * @return string
     */
    public function getGpsLon()
    {
        return $this->gpsLon;
    }

    public function removeDevice(Device $device)
    {
        $this->devices->removeElement($device);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Site %s deleted',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArchiveMessage()
    {
        $message['logMsg'] = [
            'message' => 'Site %s archived',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogRestoreMessage()
    {
        $message['logMsg'] = [
            'message' => 'Site %s restored',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Site %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return null;
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

    public function getGpsCoordinate(): ?Coordinate
    {
        if (! $this->gpsLat || ! $this->gpsLon) {
            return null;
        }

        try {
            return new Coordinate((float) $this->gpsLat, (float) $this->gpsLon);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }
}
