<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Import;

interface ImportItemValidationErrorsInterface
{
    public function getImportItem(): ImportItemInterface;

    public function getUnmappedErrors(): array;

    public function setUnmappedErrors(array $unmappedErrors): void;

    /**
     * @return mixed[][]
     */
    public function getErrors(): array;

    public function hasErrors(): bool;
}
