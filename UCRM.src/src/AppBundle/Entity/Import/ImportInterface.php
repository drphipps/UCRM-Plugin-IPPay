<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Import;

use AppBundle\Entity\Organization;
use AppBundle\Entity\User;

interface ImportInterface
{
    public const STATUS_UPLOADED = 1;
    public const STATUS_MAPPED = 2;
    public const STATUS_ITEMS_LOADING = 3;
    public const STATUS_ITEMS_LOADED = 4;
    public const STATUS_ITEMS_VALIDATING = 5;
    public const STATUS_ITEMS_VALIDATED = 6;
    public const STATUS_ENQUEUED = 7;
    public const STATUS_ITEMS_ENQUEUEING = 8;
    public const STATUS_ITEMS_ENQUEUED = 9;
    public const STATUS_SAVING = 10;
    public const STATUS_FINISHED = 11;

    // this array MUST have statuses in correct order defined by import progress, as it's used to determine
    // where we are in the import process
    public const ORDERED_STATUSES = [
        self::STATUS_UPLOADED,
        self::STATUS_MAPPED,
        self::STATUS_ITEMS_LOADING,
        self::STATUS_ITEMS_LOADED,
        self::STATUS_ITEMS_VALIDATING,
        self::STATUS_ITEMS_VALIDATED,
        self::STATUS_ENQUEUED,
        self::STATUS_ITEMS_ENQUEUEING,
        self::STATUS_ITEMS_ENQUEUED,
        self::STATUS_SAVING,
        self::STATUS_FINISHED,
    ];

    public function getId(): string;

    public function getCreatedDate(): \DateTime;

    public function setCreatedDate(\DateTime $createdDate): void;

    public function getStatus(): int;

    public function setStatus(int $status): void;

    public function isStatusDone(int $status): bool;

    public function getCsvHash(): string;

    public function setCsvHash(string $csvHash): void;

    public function getCsvMapping(): ?array;

    public function setCsvMapping(?array $csvMapping): void;

    public function getCsvDelimiter(): string;

    public function setCsvDelimiter(string $csvDelimiter): void;

    public function getCsvEnclosure(): string;

    public function setCsvEnclosure(string $csvEnclosure): void;

    public function getCsvEscape(): string;

    public function setCsvEscape(string $csvEscape): void;

    public function isCsvHasHeader(): bool;

    public function setCsvHasHeader(bool $csvHasHeader): void;

    public function getCount(): ?int;

    public function setCount(?int $count): void;

    public function getCountSuccess(): int;

    public function setCountSuccess(int $countSuccess): void;

    public function incrementSuccessCount(): void;

    public function getCountFailure(): int;

    public function setCountFailure(int $countFailure): void;

    public function incrementFailureCount(): void;

    public function getUser(): ?User;

    public function setUser(?User $user): void;

    public function getOrganization(): ?Organization;

    public function setOrganization(?Organization $organization): void;
}
