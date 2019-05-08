<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use AppBundle\Entity\Financial\Invoice;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"created_date"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EntityLogRepository")
 */
class EntityLog
{
    const INSERT = 0;
    const EDIT = 1;
    const DELETE = 2;
    const SOFT_DELETE = 17;
    const RESTORE = 3;
    const POSTPONE = 4;
    const SUSPEND = 5;
    const RECURRING_INVOICES = 6;
    const APPROVE_DRAFTS = 7;
    const DELETE_DRAFTS = 15;
    const SEND_INVOICES = 8;
    const SEND_INVITATIONS = 9;
    const LOGIN = 10;
    const PASSWORD_CHANGE = 11;
    const BACKUP_DOWNLOAD = 12;
    const BACKUP_RESTORE = 13;
    const BACKUP_UPLOAD = 14;
    const CERTIFICATE_UPLOADED = 16;
    const AIRCRM_IMPORT = 18;
    const DEVICE_BACKUP_DOWNLOAD = 19;
    const ONLINE_PAYMENT_FAILURE = 20;
    const CSV_IMPORT = 21;
    const REACTIVATE = 22;
    const PAYMENT_PLAN_CANCELED = 23;
    const PAYMENT_PLAN_DELETED = 24;
    const CLIENT_EXPORT_DOWNLOAD = 25;

    const CLIENT = 0;
    const ADMIN = 1;
    const SYSTEM = 2;

    const LOG_CHANGE_TYPE = [
        self::INSERT => 'Insert',
        self::EDIT => 'Edit',
        self::DELETE => 'Delete',
        self::SOFT_DELETE => 'Archive',
        self::RESTORE => 'Restore',
        self::POSTPONE => 'Postpone',
        self::SUSPEND => 'Suspend',
        self::RECURRING_INVOICES => 'Recurring invoices',
        self::APPROVE_DRAFTS => 'Approve drafts',
        self::DELETE_DRAFTS => 'Delete drafts',
        self::SEND_INVOICES => 'Send invoices',
        self::SEND_INVITATIONS => 'Send invitations',
        self::LOGIN => 'Login',
        self::PASSWORD_CHANGE => 'Password change',
        self::BACKUP_DOWNLOAD => 'Backup download',
        self::BACKUP_RESTORE => 'Backup restore',
        self::BACKUP_UPLOAD => 'Backup upload',
        self::CERTIFICATE_UPLOADED => 'HTTPS certificate uploaded',
        self::AIRCRM_IMPORT => 'AirCRM import',
        self::DEVICE_BACKUP_DOWNLOAD => 'Device backup download',
        self::ONLINE_PAYMENT_FAILURE => 'Online payment failed',
        self::CSV_IMPORT => 'CSV import',
        self::PAYMENT_PLAN_CANCELED => 'Subscription canceled',
        self::PAYMENT_PLAN_DELETED => 'Subscription deleted',
        self::CLIENT_EXPORT_DOWNLOAD => 'Client export downloaded',
    ];

    const USER_TYPE = [
        self::CLIENT => 'Client',
        self::ADMIN => 'Admin',
        self::SYSTEM => 'System',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="log_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", nullable=true)
     */
    protected $user;

    /**
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=true, onDelete="SET NULL")
     */
    protected $client;

    /**
     * @var Site|null
     *
     * @ORM\ManyToOne(targetEntity="Site")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="site_id", nullable=true, onDelete="SET NULL")
     */
    protected $site;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime_utc")
     */
    protected $createdDate;

    /**
     * @var string
     *
     * @ORM\Column(name="log", type="text", nullable=true)
     */
    protected $log;

    /**
     * @var int
     *
     * @ORM\Column(name="change_type", type="integer", nullable=true)
     */
    protected $changeType;

    /**
     * @var string
     *
     * @ORM\Column(name="entity", type="string", length=255, nullable=true)
     */
    protected $entity;

    /**
     * @var int
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=true)
     */
    protected $entityId;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_entity", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $parentEntity;

    /**
     * @var int|null
     *
     * @ORM\Column(name="parent_entity_id", type="integer", nullable=true)
     */
    protected $parentEntityId;

    /**
     * @var int|null
     *
     * @ORM\Column(name="user_type", type="integer", nullable=true)
     */
    protected $userType;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $createdDate
     */
    public function setCreatedDate($createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @param string $log
     */
    public function setLog($log): void
    {
        $this->log = $log;
    }

    /**
     * @return string
     */
    public function getLog(): ?string
    {
        $log = @unserialize($this->log);
        // ignore if it cannot be unserialized
        // @todo the logs really do need refactoring
        if ($log === false) {
            return null;
        }

        if ($this->changeType === self::EDIT) {
            $entityLogData = [];
            foreach ($log as $key => $value) {
                foreach ($value as $subKey => $subValue) {
                    $value[$subKey] = $subValue;
                    switch ($this->getEntity()) {
                        case Client::class:
                            if ($key === 'clientType') {
                                $value[$subKey] = Client::CLIENT_TYPE[$subValue];
                            }
                            break;
                        case DeviceInterface::class:
                            if ($key === 'type') {
                                $value[$subKey] = DeviceInterface::TYPES[$subValue];
                            }
                            break;
                        case Invoice::class:
                            if ($key === 'invoiceStatus') {
                                $value[$subKey] = Invoice::STATUS_REPLACE_STRING[$subValue];
                            }
                            break;
                        case NotificationTemplate::class:
                            if ($key === 'type') {
                                $value[$subKey] = NotificationTemplate::NOTIFICATION_TYPES[$subValue];
                            }
                            break;
                        case Payment::class:
                            if ($key === 'method') {
                                $value[$subKey] = Payment::METHOD_TYPE[$subValue];
                            }
                            break;
                        case Service::class:
                            if ($key === 'contractLengthType') {
                                $value[$subKey] = Service::CONTRACT_LENGTH_TYPE[$subValue];
                            } elseif ($key === 'discountType') {
                                $value[$subKey] = Service::DISCOUNT_TYPE[$subValue];
                            } elseif ($key === 'invoicingPeriodType') {
                                $value[$subKey] = Service::INVOICING_PERIOD_TYPE[$subValue];
                            } elseif ($key === 'status') {
                                $value[$subKey] = Service::SERVICE_STATUSES[$subValue];
                            }
                            break;
                    }
                }
                $entityLogData[$key] = $value;
            }
            $log = $entityLogData;
        }

        return serialize($log);
    }

    /**
     * @param int $changeType
     */
    public function setChangeType($changeType): void
    {
        $this->changeType = $changeType;
    }

    /**
     * @return int
     */
    public function getChangeType()
    {
        return $this->changeType;
    }

    /**
     * @param string $entity
     */
    public function setEntity($entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param int $entityId
     */
    public function setEntityId($entityId): void
    {
        $this->entityId = $entityId;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param string $parentEntity
     */
    public function setParentEntity($parentEntity): void
    {
        $this->parentEntity = $parentEntity;
    }

    /**
     * @return string
     */
    public function getParentEntity()
    {
        return $this->parentEntity;
    }

    /**
     * @param int $parentEntityId
     */
    public function setParentEntityId($parentEntityId): void
    {
        $this->parentEntityId = $parentEntityId;
    }

    /**
     * @return int
     */
    public function getParentEntityId()
    {
        return $this->parentEntityId;
    }

    public function setUser(?User $user = null): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setClient(?Client $client = null): void
    {
        $this->client = $client;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setSite(?Site $site = null): void
    {
        $this->site = $site;
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param int $userType
     */
    public function setUserType($userType): void
    {
        $this->userType = $userType;
    }

    /**
     * @return int
     */
    public function getUserType()
    {
        return $this->userType;
    }
}
