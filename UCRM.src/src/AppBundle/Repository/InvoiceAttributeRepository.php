<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\InvoiceAttribute;

class InvoiceAttributeRepository extends BaseRepository
{
    public function loadAttributes(Invoice $invoice): void
    {
        $this
            ->loadRelatedEntities(
                'attribute',
                $invoice
                    ->getAttributes()
                    ->map(
                        function (InvoiceAttribute $attribute) {
                            return $attribute->getId();
                        }
                    )
                    ->toArray()
            );
    }
}
