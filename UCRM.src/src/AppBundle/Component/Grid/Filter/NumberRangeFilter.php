<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Filter;

use Nette\Utils\Html;

class NumberRangeFilter extends BaseFilterField implements RangeFilterInterface
{
    /** @var int|null */
    private $rangeFrom;

    /** @var int|null */
    private $rangeTo;

    /**
     * @return int|null
     */
    public function getRangeFrom()
    {
        return $this->rangeFrom;
    }

    /**
     * @param int|null $rangeFrom
     */
    public function setRangeFrom($rangeFrom)
    {
        $this->rangeFrom = $rangeFrom;
    }

    /**
     * @return int|null
     */
    public function getRangeTo()
    {
        return $this->rangeTo;
    }

    /**
     * @param int|null $rangeTo
     */
    public function setRangeTo($rangeTo)
    {
        $this->rangeTo = $rangeTo;
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
    public function createControlPrototype()
    {
        $container = Html::el('div class="appInputGroup"');

        $inputFrom = Html::el('input');
        $inputFrom->addAttributes(
            [
                'name' => sprintf('%s-filter[%s_from]', $this->grid->getName(), $this->getName()),
                'type' => 'number',
                'placeholder' => $this->getGrid()->getTranslator()->trans('from'),
                'size' => 12,
                'value' => $this->getRangeFrom(),
            ]
        );
        $inputTo = Html::el('input');
        $inputTo->addAttributes(
            [
                'name' => sprintf('%s-filter[%s_to]', $this->grid->getName(), $this->getName()),
                'type' => 'number',
                'placeholder' => $this->getGrid()->getTranslator()->trans('to'),
                'size' => 12,
                'value' => $this->getRangeTo(),
            ]
        );

        $container->addHtml($inputFrom);
        $container->addHtml($inputTo);

        return $container;
    }
}
