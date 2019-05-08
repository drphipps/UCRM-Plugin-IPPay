<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Component;

use Nette\Utils\IHtmlString;

class MultiAction
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
     * @var callable
     */
    public $callback;

    /**
     * @var array
     */
    public $cssClasses = [
        'button',
    ];

    /**
     * @var string|null
     */
    public $tooltip;

    /**
     * @var string|null
     */
    public $icon;

    /**
     * @var bool
     */
    public $allowAll;

    /**
     * @var IHtmlString|string|null
     */
    public $confirmMessage;

    /**
     * @var IHtmlString|string|null
     */
    public $confirmTitle;

    /**
     * @var IHtmlString|string|null
     */
    public $confirmOkay;

    /**
     * @param IHtmlString|string|null $confirmMessage
     */
    public function __construct(
        string $name,
        string $title,
        callable $callback,
        array $cssClasses,
        $confirmMessage = null,
        string $tooltip = null,
        string $icon = null,
        bool $allowAll = false
    ) {
        $this->name = $name;
        $this->title = $title;
        $this->callback = $callback;
        $this->cssClasses = array_merge($this->cssClasses, $cssClasses);
        $this->confirmMessage = $confirmMessage;
        $this->tooltip = $tooltip;
        $this->icon = $icon;
        $this->allowAll = $allowAll;
    }
}
