<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util\Financial;

use AppBundle\Entity\Financial\FinancialItemFeeInterface;
use AppBundle\Entity\Financial\FinancialItemInterface;
use AppBundle\Entity\Financial\FinancialItemOtherInterface;
use AppBundle\Entity\Financial\FinancialItemProductInterface;
use AppBundle\Entity\Financial\FinancialItemServiceInterface;
use AppBundle\Factory\Financial\ItemFormIteratorFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ds\Map;
use Ds\Set;

final class FinancialItemSorter
{
    /**
     * Sorts the financial items collection in the same way as ItemFormIteratorFactory.
     * This is needed to have the items displayed in same order everywhere (forms and views).
     *
     * @see ItemFormIteratorFactory
     *
     * @param Collection|FinancialItemInterface[] $items
     */
    public static function sort(Collection $items): ArrayCollection
    {
        $itemsWithKnownPosition = new Map();
        $itemsWithUnknownPosition = new Set();

        foreach ($items as $item) {
            $itemPosition = $item->getItemPosition();
            if ($itemPosition === null) {
                $itemsWithUnknownPosition->add($item);
            } else {
                $itemsWithKnownPosition->put($itemPosition, $item);
            }
        }

        $itemsWithKnownPosition->ksort();

        // We first order the unknown items based on their class, to keep the order the same as ItemFormIteratorFactory.
        $itemsWithUnknownPosition = $itemsWithUnknownPosition->sorted(
            function (FinancialItemInterface $a, FinancialItemInterface $b) {
                return self::getSortPriority($a) <=> self::getSortPriority($b);
            }
        );

        // If the item has unknown position, we put it at the end. Items with known position take priority.
        $maxPosition = $itemsWithKnownPosition->isEmpty() ? -1 : (int) $itemsWithKnownPosition->last()->key;
        foreach ($itemsWithUnknownPosition as $item) {
            $itemsWithKnownPosition->put(++$maxPosition, $item);
        }

        return new ArrayCollection($itemsWithKnownPosition->toArray());
    }

    /**
     * If the sort priority is changed, change it in the ItemFormIteratorFactory too.
     *
     * @see ItemFormIteratorFactory::create()
     */
    private static function getSortPriority(FinancialItemInterface $item): int
    {
        switch (true) {
            case $item instanceof FinancialItemServiceInterface:
                return 0;
            case $item instanceof FinancialItemProductInterface:
                return 1;
            case $item instanceof FinancialItemFeeInterface:
                return 2;
            case $item instanceof FinancialItemOtherInterface:
                return 3;
            default:
                return 4;
        }
    }
}
