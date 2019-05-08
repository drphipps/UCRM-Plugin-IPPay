<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Filter;

use Nette\Utils\Html;

class BoolFilterField extends BaseFilterField
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
        $htmlElement = Html::el('div', [
            'class' => [
                'button-group',
                'button-group--filter',
            ],
        ]);

        if ($this->grid->getActiveFilter($this->name)) {
            $selected = $this->grid->getActiveFilter($this->name);
        }
        $selected = $selected ?? '';

        $name = sprintf('%s-filter[%s]', $this->grid->getName(), $this->getName());
        $options = [
            't' => $this->getGrid()->getTranslator()->trans('Yes'),
            'f' => $this->getGrid()->getTranslator()->trans('No'),
        ];

        $i = 0;
        foreach ($options as $key => $value) {
            $id = sprintf('%s-%d', $name, ++$i);
            $input = Html::el('input', [
                'type' => 'radio',
                'name' => $name,
                'id' => $id,
                'value' => $key,
                'checked' => $selected === $key ? true : false,
            ]);
            $label = Html::el('label', [
                'for' => $id,
                'class' => [
                    'button',
                    'button-group__item',
                ],
            ]);
            $label->setText($value);
            $htmlElement->addHtml($input);
            $htmlElement->addHtml($label);
        }

        return $htmlElement;
    }
}
