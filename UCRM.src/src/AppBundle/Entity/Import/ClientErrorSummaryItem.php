<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Import;

use AppBundle\Component\Import\Transformer\ConstraintViolationTransformer;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"client_error_summary_id", "hash"})
 *     }
 * )
 */
class ClientErrorSummaryItem
{
    public const TYPE_CLIENT = 'client';
    public const TYPE_SERVICE = 'service';

    /**
     * We display maximum of 3 line numbers and append ellipsis if there is more, so 4 numbers are enough.
     */
    private const LINE_NUMBERS_LIMIT = 4;

    /**
     * @var string
     *
     * @ORM\Column(type="guid")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var ClientErrorSummary
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Import\ClientErrorSummary", inversedBy="errorSummaryItems")
     * @ORM\JoinColumn(name="client_error_summary_id", nullable=false, onDelete="CASCADE")
     */
    private $errorSummary;

    /**
     * @var string - sha1 hash
     *
     * @see ConstraintViolationTransformer::toSummaryHash()
     *
     * @ORM\Column(name="hash", length=40)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column()
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default": 0})
     */
    public $count = 0;

    /**
     * @var int[]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    public $lineNumbers = [];

    /**
     * @var mixed[]
     *
     * @see ConstraintViolationTransformer::toArray()
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    private $error = [];

    public function __construct()
    {
        // @todo use what @janprochazkacz is using when available
        $this->id = Uuid::uuid4()->toString();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getErrorSummary(): ClientErrorSummary
    {
        return $this->errorSummary;
    }

    public function setErrorSummary(ClientErrorSummary $errorSummary): void
    {
        $this->errorSummary = $errorSummary;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function increaseCount(): void
    {
        ++$this->count;
    }

    public function getLineNumbers(): array
    {
        return $this->lineNumbers;
    }

    public function addLineNumber(int $lineNumber): void
    {
        if (in_array($lineNumber, $this->lineNumbers, true) || count($this->lineNumbers) >= self::LINE_NUMBERS_LIMIT) {
            return;
        }

        $this->lineNumbers[] = $lineNumber;
    }

    public function getError(): array
    {
        return $this->error;
    }

    public function setError(array $error): void
    {
        $this->error = $error;
    }
}
