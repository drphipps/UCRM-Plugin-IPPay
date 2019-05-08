<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Filter;

use Nette\Utils\Html;

class TextFilterField extends BaseFilterField
{
    public function render()
    {
        echo $this->getLabelPrototype();
        echo $this->getControlPrototype();
    }

    /**
     * @return Html
     */
    public function createControlPrototype()
    {
        $htmlElement = Html::el('input');
        $htmlElement->addAttributes([
            'name' => sprintf('%s-filter[%s]', $this->grid->getName(), $this->getName()),
            'type' => 'text',
            'placeholder' => $this->getTitle(),
        ]);

        if ($this->grid->getActiveFilter($this->name)) {
            $htmlElement->setAttribute('value', $this->grid->getActiveFilter($this->name));
        }

        return $htmlElement;
    }
}
