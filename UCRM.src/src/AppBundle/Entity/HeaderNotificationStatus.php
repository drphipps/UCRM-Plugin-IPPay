<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\HeaderNotificationStatusRepository")
 */
class HeaderNotificationStatus
{
    /**
     * @var string
     *
     * @ORM\Column(type="guid")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $id;

    /**
     * @var HeaderNotification
     *
     * @ORM\ManyToOne(targetEntity="HeaderNotification")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $headerNotification;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(referencedColumnName="user_id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected $read = false;

    public function __construct()
    {
        // @todo use what @janprochazkacz is using when available
        $this->id = Uuid::uuid4()->toString();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getHeaderNotification(): HeaderNotification
    {
        return $this->headerNotification;
    }

    public function setHeaderNotification(HeaderNotification $headerNotification): void
    {
        $this->headerNotification = $headerNotification;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function setRead(bool $read): void
    {
        $this->read = $read;
    }
}
