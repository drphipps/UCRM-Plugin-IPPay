<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Transformer;

use AppBundle\Entity\Import\ClientImportItem;

class CsvRowToClientImportItemTransformer extends AbstractCsvRowToImportItemTransformer
{
    public function transform(array $row): ClientImportItem
    {
        $item = $this->transformToItem($row, ClientImportItem::class);

        if ($item instanceof ClientImportItem) {
            return $item;
        }

        // If we have service data, but no client data, we need to show the validation error
        // and since the ServiceImportItem is not standalone, we need empty ClientImportItem.
        $item = new ClientImportItem();
        $item->setEmpty(true);

        return $item;
    }
}
