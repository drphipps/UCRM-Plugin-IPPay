<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Component;

class MultiActionGroup
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string|null
     */
    public $icon;

    /**
     * @var array
     */
    public $cssClasses = [
        'button',
        'button-group__item',
    ];

    /**
     * @var array|MultiAction[]
     */
    public $actions;

    public function __construct(string $name, string $title, array $cssClasses, array $actions, string $icon = null)
    {
        $this->name = $name;
        $this->title = $title;
        $this->cssClasses = array_merge($this->cssClasses, $cssClasses);
        $this->icon = $icon;
        $this->actions = $actions;
    }
}
