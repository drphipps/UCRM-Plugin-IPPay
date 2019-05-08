<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial;

use AppBundle\Util\Financial\FinancialItemSorter;
use Ds\Map;
use Ds\Set;
use Symfony\Component\Form\FormView;

final class ItemFormIteratorFactory
{
    public function create(FormView $form): \Traversable
    {
        $itemsWithKnownPosition = new Map();
        $itemsWithUnknownPosition = new Set();

        /**
         * If the order the items are added is changed, change it in FinancialItemSorter::getSortPriority() too.
         *
         * @see FinancialItemSorter::getSortPriority()
         */
        foreach ($form->children['financialItemServices'] as $item) {
            $this->addItem($item, $itemsWithKnownPosition, $itemsWithUnknownPosition);
        }
        foreach ($form->children['financialItemProducts'] as $item) {
            $this->addItem($item, $itemsWithKnownPosition, $itemsWithUnknownPosition);
        }
        foreach ($form->children['financialItemFees'] as $item) {
            $this->addItem($item, $itemsWithKnownPosition, $itemsWithUnknownPosition);
        }
        foreach ($form->children['financialItemOthers'] as $item) {
            $this->addItem($item, $itemsWithKnownPosition, $itemsWithUnknownPosition);
        }

        $itemsWithKnownPosition->ksort();

        // If the item has unknown position, we put it at the end. Items with known position take priority.
        $maxPosition = $itemsWithKnownPosition->isEmpty() ? -1 : (int) $itemsWithKnownPosition->last()->key;
        foreach ($itemsWithUnknownPosition as $item) {
            $itemsWithKnownPosition->put(++$maxPosition, $item);
        }

        return $itemsWithKnownPosition;
    }

    private function addItem(FormView $item, Map $itemsWithKnownPosition, Set $itemsWithUnknownPosition): void
    {
        $position = trim((string) $item->children['itemPosition']->vars['value']);
        if ($position === '') {
            $itemsWithUnknownPosition->add($item);
        } else {
            $itemsWithKnownPosition->put((int) $position, $item);
        }
    }
}
