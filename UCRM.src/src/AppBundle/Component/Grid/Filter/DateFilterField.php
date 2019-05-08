<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Filter;

use AppBundle\Component\Grid\Grid;
use Nette\Utils\Html;

class DateFilterField extends BaseFilterField implements RangeFilterInterface
{
    /** @var bool */
    private $range = false;

    /** @var string|null */
    private $rangeFrom;

    /** @var string|null */
    private $rangeTo;

    /**
     * @param string $name
     * @param string $queryIdentifier
     * @param string $title
     * @param bool   $range
     */
    public function __construct(Grid $grid, $name, $queryIdentifier, $title, $range = false)
    {
        $this->range = $range;

        parent::__construct($grid, $name, $queryIdentifier, $title);
    }

    /**
     * @return bool
     */
    public function isRange()
    {
        return $this->range;
    }

    /**
     * @return string
     */
    public function getRangeFrom()
    {
        return $this->rangeFrom;
    }

    /**
     * @param string|null $rangeFrom
     */
    public function setRangeFrom($rangeFrom)
    {
        $this->rangeFrom = $rangeFrom ?: null;
    }

    /**
     * @return string
     */
    public function getRangeTo()
    {
        return $this->rangeTo;
    }

    /**
     * @param string|null $rangeTo
     */
    public function setRangeTo($rangeTo)
    {
        $this->rangeTo = $rangeTo ?: null;
    }

    public function render()
    {
        echo $this->getLabelPrototype();
        echo $this->getControlPrototype();
    }

    public function refreshControlPrototype()
    {
        $this->controlPrototype = $this->createControlPrototype();
    }

    /**
     * @return Html
     */
    public function createLabelPrototype()
    {
        if ($this->range && ! $this->labelIcon) {
            $this->labelIcon = 'ucrm-icon--calendar-check';
        }

        return parent::createLabelPrototype();
    }

    /**
     * @return Html
     */
    public function createControlPrototype()
    {
        if ($this->range) {
            return $this->createRangeControlPrototype();
        }

        $container = Html::el('div class="appInputGroup"');
        $iconInput = Html::el('div class="appIconInput appIconInput--right appIconInput--full"');
        $icon = Html::el('div class="appIconInput__icon icon ubnt-icon--calendar-3"');
        $input = Html::el('input');
        $input->addAttributes(
            [
                'name' => sprintf('%s-filter[%s]', $this->grid->getName(), $this->getName()),
                'type' => 'text',
                'class' => 'appIconInput__input datepicker',
                'size' => 12,
            ]
        );

        if ($value = $this->grid->getActiveFilter($this->name)) {
            try {
                new \DateTime($value);
                $input->setAttribute('value', $value);
            } catch (\Exception $e) {
                $input->setAttribute('value', null);
            }
        }

        $iconInput->addHtml($icon);
        $iconInput->addHtml($input);
        $container->addHtml($iconInput);

        return $container;
    }

    /**
     * @return Html
     */
    private function createRangeControlPrototype()
    {
        $container = Html::el('div class="appInputGroup"');

        $idFrom = sprintf('%s-filter_%s_from', $this->grid->getName(), $this->getName());
        $nameFrom = sprintf('%s-filter[%s_from]', $this->grid->getName(), $this->getName());
        $idTo = sprintf('%s-filter_%s_to', $this->grid->getName(), $this->getName());
        $nameTo = sprintf('%s-filter[%s_to]', $this->grid->getName(), $this->getName());

        $inputFrom = Html::el(
            'input',
            [
                'id' => $idFrom,
                'name' => $nameFrom,
                'type' => 'text',
                'class' => 'datepicker',
                'placeholder' => $this->getGrid()->getTranslator()->trans('From'),
                'size' => 12,
                'data-datepicker-range-to' => $idTo,
            ]
        );

        $inputTo = Html::el(
            'input',
            [
                'id' => $idTo,
                'name' => $nameTo,
                'type' => 'text',
                'class' => 'datepicker',
                'placeholder' => $this->getGrid()->getTranslator()->trans('To'),
                'size' => 12,
                'data-datepicker-range-from' => $idFrom,
            ]
        );

        if ($this->getRangeFrom()) {
            try {
                new \DateTime($this->getRangeFrom());
                $inputFrom->setAttribute('value', $this->getRangeFrom());
            } catch (\Exception $e) {
                $inputFrom->setAttribute('value', null);
            }
        }

        if ($this->getRangeTo()) {
            try {
                new \DateTime($this->getRangeTo());
                $inputTo->setAttribute('value', $this->getRangeTo());
            } catch (\Exception $e) {
                $inputTo->setAttribute('value', null);
            }
        }

        $container->addHtml($inputFrom);
        $container->addHtml($inputTo);

        return $container;
    }
}
