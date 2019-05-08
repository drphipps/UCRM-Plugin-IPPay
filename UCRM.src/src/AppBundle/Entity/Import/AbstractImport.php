<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Import;

use AppBundle\Entity\Organization;
use AppBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\MappedSuperclass()
 */
abstract class AbstractImport implements ImportInterface
{
    public const DEFAULT_DELIMITER = ',';
    public const DEFAULT_ENCLOSURE = '"';
    public const DEFAULT_ESCAPE = '\\';

    public const DELIMITERS = [
        ',',
        ';',
        "\t",
        '|',
        '^',
    ];

    public const DELIMITERS_FORM = [
        'comma ,' => ',',
        'semi-colon ;' => ';',
        'tabulator' => "\t",
        'pipe |' => '|',
        'caret ^' => '^',
    ];

    public const ENCLOSURES = [
        '"',
        '\'',
        '~',
    ];

    public const ENCLOSURES_FORM = [
        'double quotes "' => '"',
        'single quotes \'' => '\'',
        'tilde ~' => '~',
    ];

    /**
     * @var string
     *
     * @ORM\Column(type="guid")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    protected $createdDate;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"default": ImportInterface::STATUS_UPLOADED})
     */
    protected $status = ImportInterface::STATUS_UPLOADED;

    /**
     * @var string - sha1 hash of first CSV row
     *
     * @ORM\Column(length = 40)
     */
    protected $csvHash;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $csvMapping;

    /**
     * @var string
     *
     * @ORM\Column(length=1, options={"default": AbstractImport::DEFAULT_DELIMITER})
     */
    protected $csvDelimiter = AbstractImport::DEFAULT_DELIMITER;

    /**
     * @var string
     *
     * @ORM\Column(length=1, options={"default": AbstractImport::DEFAULT_ENCLOSURE})
     */
    protected $csvEnclosure = AbstractImport::DEFAULT_ENCLOSURE;

    /**
     * @var string
     *
     * @ORM\Column(length=1, options={"default": AbstractImport::DEFAULT_ESCAPE})
     */
    protected $csvEscape = AbstractImport::DEFAULT_ESCAPE;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    protected $csvHasHeader = true;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $count;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default":0})
     */
    protected $countSuccess = 0;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default":0})
     */
    protected $countFailure = 0;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="user_id", onDelete="SET NULL")
     */
    protected $user;

    /**
     * @var Organization|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organization")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="organization_id", onDelete="SET NULL")
     */
    protected $organization;

    public function __construct()
    {
        // @todo use what @janprochazkacz is using when available
        $this->id = Uuid::uuid4()->toString();
        $this->createdDate = new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isStatusDone(int $status): bool
    {
        return array_search($this->status, ImportInterface::ORDERED_STATUSES, true)
            >= array_search($status, ImportInterface::ORDERED_STATUSES, true);
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getCsvHash(): string
    {
        return $this->csvHash;
    }

    public function setCsvHash(string $csvHash): void
    {
        $this->csvHash = $csvHash;
    }

    public function getCsvMapping(): ?array
    {
        return $this->csvMapping;
    }

    public function setCsvMapping(?array $csvMapping): void
    {
        $this->csvMapping = $csvMapping;
    }

    public function getCsvDelimiter(): string
    {
        return $this->csvDelimiter;
    }

    public function setCsvDelimiter(string $csvDelimiter): void
    {
        $this->csvDelimiter = $csvDelimiter;
    }

    public function getCsvEnclosure(): string
    {
        return $this->csvEnclosure;
    }

    public function setCsvEnclosure(string $csvEnclosure): void
    {
        $this->csvEnclosure = $csvEnclosure;
    }

    public function getCsvEscape(): string
    {
        return $this->csvEscape;
    }

    public function setCsvEscape(string $csvEscape): void
    {
        $this->csvEscape = $csvEscape;
    }

    public function isCsvHasHeader(): bool
    {
        return $this->csvHasHeader;
    }

    public function setCsvHasHeader(bool $csvHasHeader): void
    {
        $this->csvHasHeader = $csvHasHeader;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): void
    {
        $this->count = $count;
    }

    public function getCountSuccess(): int
    {
        return $this->countSuccess;
    }

    public function setCountSuccess(int $countSuccess): void
    {
        $this->countSuccess = $countSuccess;
    }

    public function incrementSuccessCount(): void
    {
        ++$this->countSuccess;
    }

    public function getCountFailure(): int
    {
        return $this->countFailure;
    }

    public function setCountFailure(int $countFailure): void
    {
        $this->countFailure = $countFailure;
    }

    public function incrementFailureCount(): void
    {
        ++$this->countFailure;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
    }
}
