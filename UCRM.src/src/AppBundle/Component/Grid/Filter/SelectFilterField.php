<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid\Filter;

use AppBundle\Component\Grid\Grid;
use Nette\Utils\Html;

class SelectFilterField extends BaseFilterField
{
    public const OPTION_SEPARATOR = ':option_separator:';

    /**
     * @var bool
     */
    private $allowSearch;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var callable|null
     */
    private $filterCallback;

    /**
     * @var bool
     */
    private $allowClear = true;

    /**
     * @var string|null
     */
    protected $overrideCssClasses = null;

    public function __construct(
        Grid $grid,
        string $name,
        string $queryIdentifier,
        string $title,
        array $options,
        string $tooltip = '',
        string $placeholder = '',
        bool $allowSearch = false
    ) {
        $this->options = $options;
        $this->allowSearch = $allowSearch;

        parent::__construct($grid, $name, $queryIdentifier, $title, $tooltip, $placeholder);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
        $this->controlPrototype = $this->createControlPrototype();
    }

    public function setOverrideCssClasses(?string $overrideCssClasses): void
    {
        $this->overrideCssClasses = $overrideCssClasses;
        $this->controlPrototype = $this->createControlPrototype();
    }

    public function getFilterCallback(): ?callable
    {
        return $this->filterCallback;
    }

    public function setFilterCallback(?callable $filterCallback): void
    {
        $this->filterCallback = $filterCallback;
    }

    public function setAllowClear(bool $allowClear): void
    {
        $this->allowClear = $allowClear;
        $this->controlPrototype = $this->createControlPrototype();
    }

    public function render(): void
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
        $htmlElement = Html::el('select');

        $attributes = [
            'name' => sprintf('%s-filter[%s]', $this->grid->getName(), $this->getName()),
            'class' => $this->overrideCssClasses
                ? $this->overrideCssClasses
                : sprintf(
                    '%s%s',
                    'select2',
                    ! $this->allowSearch ? ' select2--no-search' : ''
                ),
            'placeholder' => $this->title ?: $this->placeholder,
        ];

        if ($this->allowClear) {
            $attributes['data-allow-clear'] = 'true';
        }

        $htmlElement->addAttributes($attributes);
        $selected = $this->grid->getActiveFilter($this->name) ?? $this->defaultValue;

        $this->options = ['' => '-'] + $this->options;

        foreach ($this->options as $value => $label) {
            if (is_array($label)) {
                $group = Html::el(
                    'optgroup',
                    [
                        'label' => $value,
                    ]
                );

                foreach ($label as $val => $lab) {
                    $group->addHtml(
                        $this->createOption(
                            $val,
                            $lab,
                            (string) $selected === (string) $val,
                            $value === self::OPTION_SEPARATOR
                        )
                    );
                }

                $htmlElement->addHtml($group);
            } else {
                $htmlElement->addHtml(
                    $this->createOption(
                        $value,
                        $label,
                        (string) $selected === (string) $value,
                        $value === self::OPTION_SEPARATOR
                    )
                );
            }
        }

        return $htmlElement;
    }

    protected function createOption(
        $value,
        string $label,
        bool $selected = false,
        bool $disabled = false,
        array $attributes = []
    ): Html {
        $option = Html::el(
            'option',
            array_merge(
                [
                    'value' => $value,
                    'selected' => $selected,
                    'disabled' => $disabled,
                ],
                $attributes
            )
        );
        $option->setText($label);

        return $option;
    }
}
