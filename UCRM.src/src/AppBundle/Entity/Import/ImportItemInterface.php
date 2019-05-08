<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Import;

interface ImportItemInterface
{
    public function getId(): string;

    public function getLineNumber(): int;

    public function isDoImport(): bool;

    public function setDoImport(bool $doImport): void;

    public function getErrorSummaryType(): string;
}
