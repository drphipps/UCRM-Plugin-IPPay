<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid\Filter;

use Nette\Utils\Html;

class MultipleSelectFilterField extends SelectFilterField
{
    public function createControlPrototype(): Html
    {
        $htmlElement = Html::el('select');
        $htmlElement->addAttributes(
            [
                'name' => sprintf(
                    '%s-filter[%s][]',
                    $this->grid->getName(),
                    $this->getName()
                ),
                'class' => $this->overrideCssClasses ?: 'select2',
                'multiple' => true,
                'placeholder' => $this->placeholder,
            ]
        );

        $selected = null;
        if ($this->grid->getActiveFilter($this->name)) {
            $selected = $this->grid->getActiveFilter($this->name);
            if (is_array($selected)) {
                array_map(
                    function ($val) {
                        return (string) $val;
                    },
                    $selected
                );
            } else {
                $selected = null;
            }
        }

        foreach ($this->options as $value => $option) {
            if ($option['items'] ?? null) {
                $group = Html::el(
                    'optgroup',
                    [
                        'label' => $option['label'],
                    ]
                );

                foreach ($option['items'] as $val => $lab) {
                    $group->addHtml(
                        $this->createOption(
                            $val,
                            $lab,
                            $this->isOptionSelected((string) $val, $selected),
                            $value === self::OPTION_SEPARATOR
                        )
                    );
                }

                $htmlElement->addHtml($group);
            } else {
                $htmlElement->addHtml(
                    $this->createOption(
                        $value,
                        $option['label'],
                        $this->isOptionSelected((string) $value, $selected),
                        $value === self::OPTION_SEPARATOR,
                        $option['attributes'] ?? []
                    )
                );
            }
        }

        return $htmlElement;
    }

    private function isOptionSelected(string $value, $selected): bool
    {
        return $optionSelected = is_array($selected)
            ? in_array($value, $selected, true)
            : $selected === $value;
    }
}
