<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Security\PasswordStrengthInterface;
use AppBundle\Util\AvatarColors;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Entity\TicketGroup;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\Table(
 *      name="`user`",
 *      indexes={
 *          @ORM\Index(columns={"deleted_at"}),
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class User implements AdvancedUserInterface, \Serializable, LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface, TwoFactorInterface, BackupCodeInterface, PasswordStrengthInterface
{
    use SoftDeleteableTrait;

    public const ROLE_CLIENT = 'ROLE_CLIENT';
    public const ROLE_WIZARD = 'ROLE_WIZARD';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ADMIN_ROLES = [
        self::ROLE_WIZARD,
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN,
    ];

    public const USER_ADMIN = 'admin';
    public const USER_ADMIN_PASSWORD = '$2a$04$t037oNDU3jvMfhStifNEEOklLEDhOr4FvkGYSDxb.UdnnZ/i8pJZa';

    public const USER_ORDINARY_ADMIN = 'ordinary_admin';
    public const USER_ORDINARY_ADMIN_PASSWORD = '$2y$12$8M7W.XT/ZRWDAh7Wdb.L/uH03qPbF59S44o1YWGr5qE1p55Mf.roO';

    public const USER_DENIED_ADMIN = 'denied_admin';
    public const USER_DENIED_ADMIN_PASSWORD = '$2y$12$nMnGmhVR18dauOHE/aCONOly.lD9rPLnmQ6xJEff1wOdgqkleruPu';

    /**
     * @ORM\Column(name="user_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="citext", length=320, unique=true, nullable=true)
     * @Assert\Length(max = 320)
     * @Assert\NotBlank(groups={"User"})
     * @CustomAssert\UniqueLogin(groups={"Default", "CsvUser"})
     */
    protected $username;

    /**
     * @var string
     *
     * @ORM\Column(type="citext", length=320, nullable=true)
     * @Assert\Length(max = 320)
     * @Assert\NotBlank(groups={"User"})
     * @Assert\Email(
     *     strict=true
     * )
     */
    protected $email;

    /**
     * @var string|null
     *
     * @Assert\NotBlank(groups={"newUser"})
     * @Assert\Length(max=72, min=8, minMessage="new password is too short")
     * @CustomAssert\PasswordStrength(groups={"User"})
     */
    protected $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64)
     */
    protected $password;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    protected $isActive;

    /**
     * @var string|null
     *
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255, groups={"Default", "CsvUser"})
     * @Assert\Expression(
     *     expression="value or not this.getClient() or this.getClient().getClientType() === constant('AppBundle\\Entity\\Client::TYPE_COMPANY')",
     *     message="This field is required.",
     *     groups={"Default", "CsvUser"}
     * )
     */
    protected $firstName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255, groups={"Default", "CsvUser"})
     * @Assert\Expression(
     *     expression="value or not this.getClient() or this.getClient().getClientType() === constant('AppBundle\\Entity\\Client::TYPE_COMPANY')",
     *     message="This field is required.",
     *     groups={"Default", "CsvUser"}
     * )
     */
    protected $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, options={"comment":"To determine base application roles, e.g. CLIENT, ADMIN or SUPERADMIN"})
     * @Assert\Length(max = 100)
     * @Assert\NotBlank()
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(name="confirmation_token", type="string", length=255, nullable=true, options={"comment":"Random string sent to the user email address in order to verify it"})
     * @Assert\Length(max = 255)
     */
    protected $confirmationToken;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="password_requested_at", type="datetime_utc", nullable=true, options={"comment":"Datetime of user's request for reset password"})
     */
    protected $passwordRequestedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="first_login_token", type="string", length=255, nullable=true, options={"comment":"Random string sent to the user email address in order to verify first login"})
     * @Assert\Length(max = 255)
     */
    protected $firstLoginToken;

    /**
     * @var UserGroup|null
     *
     * @ORM\ManyToOne(targetEntity="UserGroup", inversedBy="users")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="group_id", nullable=true)
     */
    protected $group;

    /**
     * @var Client|null
     *
     * @ORM\OneToOne(targetEntity="Client", mappedBy="user", cascade={"remove"})
     */
    protected $client;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     */
    protected $lastLogin;

    /**
     * @var Locale|null
     *
     * @ORM\ManyToOne(targetEntity="Locale")
     * @ORM\JoinColumn(referencedColumnName="locale_id")
     */
    protected $locale;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $googleOAuthToken;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=1024, nullable=true)
     * @CustomAssert\UniqueGoogleCalendarId()
     */
    protected $googleCalendarId;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     */
    protected $nextGoogleCalendarSynchronization;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $googleSynchronizationErrorNotificationSent = false;

    /**
     * @var UserPersonalization
     *
     * @ORM\OneToOne(targetEntity="UserPersonalization", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn()
     */
    protected $userPersonalization;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    protected $googleAuthenticatorSecret;

    /**
     * @var array
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    protected $backupCodes = [];

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default": 0})
     */
    protected $twoFactorFailureCount = 0;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     */
    protected $twoFactorFailureResetAt;

    /**
     * @var Collection|Shortcut[]
     *
     * @ORM\OneToMany(targetEntity="Shortcut", mappedBy="user", orphanRemoval=true)
     * @ORM\OrderBy({"sequence" = "ASC"})
     */
    protected $shortcuts;

    /**
     * @var Collection|TicketGroup[]
     *
     * @ORM\ManyToMany(targetEntity="TicketingBundle\Entity\TicketGroup", inversedBy="users", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(referencedColumnName="user_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"name" = "ASC"})
     */
    protected $ticketGroups;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * Color in hex format (#abc123).
     *
     * @var string|null
     *
     * @ORM\Column(length=7, nullable=true)
     * @Assert\Length(max = 7)
     * @Assert\Regex("/^#(?:[0-9a-fA-F]{3}){1,2}$/")
     */
    protected $avatarColor;

    /**
     * @var string|null
     *
     * @ORM\Column(length=510, nullable=true)
     */
    protected $fullName;

    public function __construct()
    {
        $this->isActive = true;
        $this->shortcuts = new ArrayCollection();
        $this->ticketGroups = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->avatarColor = AvatarColors::getRandom();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function createFullName()
    {
        $this->fullName = $this->getFirstName() || $this->getLastName()
            ? trim(sprintf('%s %s', $this->getFirstName(), $this->getLastName()))
            : $this->username;
    }

    public function setDeletedAt(\DateTime $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        if ($deletedAt) {
            // Move username to last name if first and last name was not filled.
            // This is to allow displaying deleted admin user's name on job details, in logs, etc.
            if ($this->getRole() !== self::ROLE_CLIENT && ! $this->getFirstName() && ! $this->getLastName()) {
                $this->setLastName($this->getUsername());
            }

            // GDPR - remove all client data
            if ($this->getRole() === self::ROLE_CLIENT) {
                $this->setFirstName(null);
                $this->setLastName(null);
            }

            $this->setClient(null);
            $this->setUsername(null);
            $this->setEmail(null);
            $this->setPassword('');
            $this->setIsActive(false);
            $this->setFirstLoginToken(null);
            $this->setConfirmationToken(null);
            $this->setPasswordRequestedAt(null);
            $this->setUserPersonalization(null);
            $this->removeGoogleCalendar();
            $this->shortcuts->clear();
        }
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, self::ADMIN_ROLES, true);
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        // in case of using bcrypt there is no need to use salt
        return null;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param string $password
     */
    public function setPlainPassword($password): User
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return [$this->role];
    }

    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username.
     */
    public function setUsername(string $username = null): User
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password.
     */
    public function setPassword(string $password): User
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set isActive.
     */
    public function setIsActive(bool $isActive): User
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    public function isAccountNonExpired(): bool
    {
        return true;
    }

    public function isAccountNonLocked(): bool
    {
        return true;
    }

    public function isCredentialsNonExpired(): bool
    {
        return true;
    }

    public function isEnabled(): bool
    {
        return $this->isActive && ($this->getClient() ? ! $this->getClient()->isDeleted() : true);
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setGroup(?UserGroup $group): void
    {
        $this->group = $group;
    }

    public function getGroup(): ?UserGroup
    {
        return $this->group;
    }

    public function setLocale(?Locale $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): ?Locale
    {
        return $this->locale;
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize(
            [
                $this->id,
                $this->username,
                $this->password,
                $this->role,
                $this->email,
                $this->firstName,
                $this->lastName,
                $this->firstLoginToken,
                // see section on salt below
                // $this->salt,
                $this->isActive,
                $this->createdAt,
            ]
        );
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            $this->role,
            $this->email,
            $this->firstName,
            $this->lastName,
            $this->firstLoginToken,
            // see section on salt below
            // $this->salt
            $this->isActive,
            $this->createdAt
            ) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUsername() ?? '';
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setRole(string $role): User
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    public function setConfirmationToken(string $confirmationToken = null): User
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    public function setPasswordRequestedAt(\DateTime $date = null): User
    {
        $this->passwordRequestedAt = $date;

        return $this;
    }

    /**
     * Gets the timestamp that the user requested a password reset.
     *
     * @return \DateTime|null
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    public function isPasswordRequestExpired(int $ttl): bool
    {
        if (! $this->getPasswordRequestedAt() instanceof \DateTime) {
            return true;
        }

        return $this->getPasswordRequestedAt()->getTimestamp() + $ttl <= time();
    }

    /**
     * @return string
     */
    public function getFirstLoginToken()
    {
        return $this->firstLoginToken;
    }

    public function setFirstLoginToken(string $firstLoginToken = null): User
    {
        $this->firstLoginToken = $firstLoginToken;

        return $this;
    }

    public function canDoFirstLogin(): bool
    {
        if ($this->getRole() === self::ROLE_CLIENT &&
            $this->getIsActive() === false &&
            null !== $this->getFirstLoginToken()
        ) {
            return true;
        }

        return false;
    }

    public function setClient(Client $client = null): User
    {
        $this->client = $client;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTime $lastLogin): User
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getGoogleOAuthToken(): ?string
    {
        return $this->googleOAuthToken;
    }

    public function setGoogleOAuthToken(?string $googleOAuthToken): void
    {
        $this->googleOAuthToken = $googleOAuthToken;
    }

    public function getGoogleCalendarId(): ?string
    {
        return $this->googleCalendarId;
    }

    public function setGoogleCalendarId(?string $googleCalendarId): void
    {
        $this->googleCalendarId = $googleCalendarId;
    }

    public function getNextGoogleCalendarSynchronization(): ?\DateTime
    {
        return $this->nextGoogleCalendarSynchronization;
    }

    public function setNextGoogleCalendarSynchronization(?\DateTime $nextGoogleCalendarSynchronization): void
    {
        $this->nextGoogleCalendarSynchronization = $nextGoogleCalendarSynchronization;
    }

    public function isGoogleSynchronizationErrorNotificationSent(): bool
    {
        return $this->googleSynchronizationErrorNotificationSent;
    }

    public function setGoogleSynchronizationErrorNotificationSent(bool $notificationSent): void
    {
        $this->googleSynchronizationErrorNotificationSent = $notificationSent;
    }

    public function getUserPersonalization(): UserPersonalization
    {
        if (! $this->userPersonalization) {
            $this->userPersonalization = new UserPersonalization();
        }

        return $this->userPersonalization;
    }

    public function setUserPersonalization(?UserPersonalization $userPersonalization): void
    {
        $this->userPersonalization = $userPersonalization;
    }

    public function setId(int $id): User
    {
        $this->id = $id;

        return $this;
    }

    public function getIdentification(): string
    {
        if ($this->client) {
            return $this->client->getNameForView();
        }

        $name = trim(implode(' ', [$this->firstName, $this->lastName]));
        if (! empty($name)) {
            return $name;
        }

        if (! empty(trim($this->username))) {
            return trim($this->username);
        }

        return trim($this->email);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'User %s deleted',
            'replacements' => $this->getNameForView(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArchiveMessage()
    {
        $message['logMsg'] = [
            'message' => 'User %s archived',
            'replacements' => $this->getNameForView(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogRestoreMessage()
    {
        $message['logMsg'] = [
            'message' => 'User %s restored',
            'replacements' => $this->getNameForView(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'User %s added',
            'replacements' => $this->getNameForView(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns(): array
    {
        return [
            'confirmationToken',
            'firstLoginToken',
            'password',
            'passwordRequestedAt',
            'twoFactorFailureCount',
            'twoFactorFailureResetAt',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return $this->getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return $this->getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage(): array
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getNameForView(),
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * @return string
     */
    public function getNameForView()
    {
        $name = sprintf('%s %s', $this->getFirstName(), $this->getLastName());

        if (empty(trim($name))) {
            return $this->getUsername();
        }

        return $name;
    }

    public function removeGoogleCalendar(): void
    {
        $this->setGoogleOAuthToken(null);
        $this->setGoogleCalendarId(null);
        $this->setNextGoogleCalendarSynchronization(null);
    }

    public function isGoogleCalendarSynchronizationPossible(): bool
    {
        return $this->googleOAuthToken && $this->googleCalendarId;
    }

    public function isDefaultWizard(): bool
    {
        return $this->username === self::USER_ADMIN
            && $this->password === self::USER_ADMIN_PASSWORD;
    }

    /**
     * {@inheritdoc}
     */
    public function isGoogleAuthenticatorEnabled(): bool
    {
        return (bool) $this->googleAuthenticatorSecret;
    }

    /**
     * {@inheritdoc}
     */
    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function getGoogleAuthenticatorSecret(): string
    {
        return $this->googleAuthenticatorSecret ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setGoogleAuthenticatorSecret($googleAuthenticatorSecret): void
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    }

    public function getBackupCodes(): array
    {
        return $this->backupCodes;
    }

    public function setBackupCodes(array $backupCodes): void
    {
        $this->backupCodes = $backupCodes;
    }

    public function getTwoFactorFailureCount(): int
    {
        return $this->twoFactorFailureCount;
    }

    public function setTwoFactorFailureCount(int $twoFactorFailureCount): void
    {
        $this->twoFactorFailureCount = $twoFactorFailureCount;
    }

    public function getTwoFactorFailureResetAt(): ?\DateTime
    {
        return $this->twoFactorFailureResetAt;
    }

    public function setTwoFactorFailureResetAt(?\DateTime $twoFactorFailureResetAt): void
    {
        $this->twoFactorFailureResetAt = $twoFactorFailureResetAt;
    }

    /**
     * {@inheritdoc}
     */
    public function isBackupCode(string $code): bool
    {
        return array_key_exists($code, $this->backupCodes) && $this->backupCodes[$code];
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateBackupCode(string $code): void
    {
        if (array_key_exists($code, $this->backupCodes)) {
            $this->backupCodes[$code] = false;
        }
    }

    public function addShortcut(Shortcut $shortcut): void
    {
        $this->shortcuts[] = $shortcut;
    }

    public function removeShortcut(Shortcut $shortcut): void
    {
        $this->shortcuts->removeElement($shortcut);
    }

    /**
     * @return Collection|Shortcut[]
     */
    public function getShortcuts()
    {
        return $this->shortcuts;
    }

    /**
     * @return Collection|TicketGroup[]
     */
    public function getTicketGroups(): Collection
    {
        return $this->ticketGroups;
    }

    public function addTicketGroup(TicketGroup $ticketGroup): void
    {
        if ($this->ticketGroups->contains($ticketGroup)) {
            return;
        }

        $this->ticketGroups->add($ticketGroup);
        $ticketGroup->addUser($this);
    }

    public function removeTicketGroup(TicketGroup $ticketGroup): void
    {
        if (! $this->ticketGroups->contains($ticketGroup)) {
            return;
        }

        $this->ticketGroups->removeElement($ticketGroup);
        $ticketGroup->removeUser($this);
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getAvatarColor(): ?string
    {
        return $this->avatarColor;
    }

    public function setAvatarColor(?string $avatarColor): void
    {
        $this->avatarColor = $avatarColor;
    }

    public function getPasswordStrengthExtraData(): array
    {
        return array_filter(
            [
                $this->username,
                $this->firstName,
                $this->lastName,
                $this->email,
            ]
        );
    }

    public function shouldCheckPasswordStrength(): bool
    {
        return $this->isAdmin();
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): void
    {
        $this->fullName = $fullName;
    }
}
