<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial\ItemData;

use AppBundle\Entity\Client;
use AppBundle\Entity\Product;
use AppBundle\Factory\Financial\FinancialItemProductFactory;

class ProductItemDataFactory
{
    /**
     * @var FinancialItemProductFactory
     */
    private $financialItemProductFactory;

    public function __construct(FinancialItemProductFactory $financialItemProductFactory)
    {
        $this->financialItemProductFactory = $financialItemProductFactory;
    }

    public function create(Product $product, Client $client): array
    {
        $item = $this->financialItemProductFactory->createInvoiceItem($product, $client);

        return [
            'product' => [
                'id' => $item->getProduct()->getId(),
            ],
            'label' => htmlspecialchars($item->getLabel() ?? '', ENT_QUOTES),
            'price' => $item->getPrice(),
            'quantity' => $item->getQuantity(),
            'unit' => htmlspecialchars($item->getUnit() ?? '', ENT_QUOTES),
            'tax1' => $item->getTax1() ? $item->getTax1()->getId() : null,
            'tax2' => $item->getTax2() ? $item->getTax2()->getId() : null,
            'tax3' => $item->getTax3() ? $item->getTax3()->getId() : null,
            'total' => $item->getTotal(),
        ];
    }
}
