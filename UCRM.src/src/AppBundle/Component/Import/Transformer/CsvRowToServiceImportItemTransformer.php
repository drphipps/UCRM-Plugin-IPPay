<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Transformer;

use AppBundle\Entity\Import\ServiceImportItem;

class CsvRowToServiceImportItemTransformer extends AbstractCsvRowToImportItemTransformer
{
    public function transform(array $row): ?ServiceImportItem
    {
        $item = $this->transformToItem($row, ServiceImportItem::class);

        return $item instanceof ServiceImportItem
            ? $item
            : null;
    }
}
