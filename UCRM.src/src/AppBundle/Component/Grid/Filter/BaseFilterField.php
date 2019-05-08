<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Filter;

use AppBundle\Component\Grid\Grid;
use Nette\Utils\Html;

abstract class BaseFilterField
{
    /**
     * @var string
     */
    protected $queryIdentifier;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Html
     */
    protected $labelPrototype;

    /**
     * @var Html
     */
    protected $controlPrototype;

    /**
     * @var Grid
     */
    protected $grid;

    /**
     * @var mixed
     */
    protected $defaultValue;

    /**
     * @var string
     */
    protected $tooltip;

    /**
     * @var string
     */
    protected $placeholder;

    /**
     * @var string|null
     */
    protected $labelIcon;

    public function __construct(
        Grid $grid,
        string $name,
        string $queryIdentifier,
        string $title,
        string $tooltip = '',
        string $placeholder = ''
    ) {
        $this->grid = $grid;
        $this->name = $name;
        $this->queryIdentifier = $queryIdentifier;
        $this->title = $title;
        $this->tooltip = $tooltip;
        $this->placeholder = $placeholder;
        $this->labelPrototype = $this->createLabelPrototype();
        $this->controlPrototype = $this->createControlPrototype();
    }

    /**
     * @return string
     */
    abstract public function render();

    /**
     * @return Html
     */
    abstract public function createControlPrototype();

    /**
     * @return string
     */
    public function getQueryIdentifier()
    {
        return $this->queryIdentifier;
    }

    /**
     * @param string $queryIdentifier
     *
     * @return $this
     */
    public function setQueryIdentifier($queryIdentifier)
    {
        $this->queryIdentifier = $queryIdentifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getLabelIcon()
    {
        return $this->labelIcon;
    }

    /**
     * @return $this
     */
    public function setLabelIcon(string $labelIcon = null)
    {
        $this->labelIcon = $labelIcon;
        $this->labelPrototype = $this->createLabelPrototype();

        return $this;
    }

    /**
     * @return Html
     */
    public function createLabelPrototype()
    {
        $label = Html::el('label');
        if ($this->labelIcon) {
            $label->setAttribute('data-tooltip', $this->getGrid()->getTranslator()->trans($this->getTitle()));
            $label->setHtml(
                Html::el(
                    'span',
                    [
                        'class' => sprintf('icon %s', $this->labelIcon),
                    ]
                )
            );
        } else {
            $label->setText($this->getGrid()->getTranslator()->trans($this->getTitle()));
        }

        if ($this->tooltip) {
            $tooltip = Html::el('span');
            $tooltip->appendAttribute('class', 'help-icon')
                ->data('tooltip', $this->tooltip);

            $label->addHtml($tooltip);
        }

        return $label;
    }

    /**
     * @return Html
     */
    public function getLabelPrototype()
    {
        return $this->labelPrototype;
    }

    /**
     * @return Html
     */
    public function getControlPrototype()
    {
        return $this->controlPrototype;
    }

    /**
     * @return Grid
     */
    public function getGrid()
    {
        return $this->grid;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
        $this->labelPrototype = $this->createLabelPrototype();
        $this->controlPrototype = $this->createControlPrototype();

        return $this;
    }
}
