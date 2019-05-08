<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Filter;

use AppBundle\Component\Grid\Grid;
use Nette\Utils\Html;

class ElasticFilterField extends BaseFilterField
{
    /**
     * @var string
     */
    protected $elasticType;

    public function __construct(
        Grid $grid,
        string $name,
        string $queryIdentifier,
        string $title,
        string $elasticType,
        string $placeholder
    ) {
        $this->elasticType = $elasticType;

        parent::__construct($grid, $name, $queryIdentifier, $title, '', $placeholder);
    }

    public function render()
    {
        echo $this->getLabelPrototype();
        echo $this->getControlPrototype();
    }

    public function createLabelPrototype(): Html
    {
        return Html::el();
    }

    public function createControlPrototype(): Html
    {
        $iconInput = Html::el(
            'div',
            [
                'class' => 'appIconInput appIconInput--left',
            ]
        );
        $icon = Html::el(
            'div',
            [
                'class' => 'appIconInput__icon icon ucrm-icon--search',
            ]
        );
        $input = Html::el('input');
        $input->addAttributes(
            [
                'name' => sprintf('%s-filter[%s]', $this->grid->getName(), $this->getName()),
                'type' => 'text',
                'placeholder' => $this->placeholder ?: '',
                'data-tooltip' => $this->getTitle(),
                'class' => 'appIconInput__input',
            ]
        );

        if ($this->grid->getActiveFilter($this->name)) {
            $input->setAttribute('value', $this->grid->getActiveFilter($this->name));
        }

        $iconInput->addHtml($icon);
        $iconInput->addHtml($input);

        return $iconInput;
    }

    public function getElasticType(): string
    {
        return $this->elasticType;
    }
}
