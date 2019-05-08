<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Filter;

use Nette\Utils\Html;

class RadioFilterField extends SelectFilterField
{
    /**
     * @var bool
     */
    private $isNullFilter = false;

    public function isNullFilter(): bool
    {
        return $this->isNullFilter;
    }

    /**
     * @return $this
     */
    public function setIsNullFilter(bool $isNullFilter): self
    {
        $this->isNullFilter = $isNullFilter;

        return $this;
    }

    public function render(): void
    {
        echo $this->getLabelPrototype();
        echo $this->getControlPrototype();
    }

    public function createControlPrototype(): Html
    {
        $control = Html::el(
            'div',
            [
                'class' => 'radio-list',
            ]
        );

        $selected = $this->grid->getActiveFilter($this->name) ?? $this->defaultValue;
        foreach ($this->options as $key => $value) {
            $option = Html::el(
                'input',
                [
                    'type' => 'radio',
                    'name' => sprintf('%s-filter[%s]', $this->grid->getName(), $this->getName()),
                    'value' => $key,
                    'checked' => (string) $selected == (string) $key ? true : false,
                ]
            );

            $label = Html::el('label');
            $label->addHtml($option . $value);

            $control->addHtml($label);
        }

        return $control;
    }
}
