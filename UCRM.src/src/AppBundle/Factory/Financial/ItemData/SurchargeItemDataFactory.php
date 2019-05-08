<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial\ItemData;

use AppBundle\Entity\ServiceSurcharge;
use AppBundle\Factory\Financial\FinancialItemSurchargeFactory;

class SurchargeItemDataFactory
{
    /**
     * @var FinancialItemSurchargeFactory
     */
    private $financialItemSurchargeFactory;

    public function __construct(FinancialItemSurchargeFactory $financialItemSurchargeFactory)
    {
        $this->financialItemSurchargeFactory = $financialItemSurchargeFactory;
    }

    public function create(ServiceSurcharge $serviceSurcharge): array
    {
        $item = $this->financialItemSurchargeFactory->createInvoiceItem($serviceSurcharge);

        return [
            'surcharge' => [
                'id' => $item->getServiceSurcharge()->getId(),
            ],
            'service' => [
                'id' => $item->getService()->getId(),
            ],
            'label' => htmlspecialchars($item->getLabel() ?? '', ENT_QUOTES),
            'price' => $item->getPrice(),
            'total' => $item->getTotal(),
            'tax1' => $item->getTax1() ? $item->getTax1()->getId() : null,
            'tax2' => $item->getTax2() ? $item->getTax2()->getId() : null,
            'tax3' => $item->getTax3() ? $item->getTax3()->getId() : null,
        ];
    }
}
