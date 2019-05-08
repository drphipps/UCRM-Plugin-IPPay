<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial\ItemData;

use AppBundle\Entity\Fee;
use AppBundle\Factory\Financial\FinancialItemFeeFactory;

class FeeItemDataFactory
{
    /**
     * @var FinancialItemFeeFactory
     */
    private $financialItemFeeFactory;

    public function __construct(FinancialItemFeeFactory $financialItemFeeFactory)
    {
        $this->financialItemFeeFactory = $financialItemFeeFactory;
    }

    public function create(Fee $fee): array
    {
        $item = $this->financialItemFeeFactory->createInvoiceItem($fee);

        return [
            'fee' => [
                'id' => $item->getFee()->getId(),
            ],
            'label' => htmlspecialchars($item->getLabel() ?? '', ENT_QUOTES),
            'price' => $item->getPrice(),
            'quantity' => $item->getQuantity(),
            'tax1' => $item->getTax1() ? $item->getTax1()->getId() : null,
            'tax2' => $item->getTax2() ? $item->getTax2()->getId() : null,
            'tax3' => $item->getTax3() ? $item->getTax3()->getId() : null,
            'total' => $item->getTotal(),
        ];
    }
}
