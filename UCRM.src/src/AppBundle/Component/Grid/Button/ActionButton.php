<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Button;

use Nette\Utils\Html;

class ActionButton
{
    const KEY_CONFIRM_MESSAGE = 'confirm';
    const KEY_TOOLTIP = 'tooltip';

    /**
     * @var string
     */
    private $route;

    /**
     * @var array
     */
    private $routeParameters = [];

    /**
     * @var string|null
     */
    private $title;

    /**
     * @var array
     */
    private $cssClasses = [];

    /**
     * @var bool
     */
    private $cssClassesOverride = false;

    /**
     * @var array
     */
    private $disabledCssClasses = [];

    /**
     * @var bool
     */
    private $disabledCssClassesOverride = false;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    protected $renderConditions = [];

    /**
     * If button cannot be rendered due to render conditions, render invisible substitute instead.
     * Used to keep column positions the same.
     *
     * @var bool
     */
    protected $renderSubstitute = false;

    /**
     * @var array
     */
    protected $disabledConditions = [];

    /**
     * @var string|null
     */
    private $disabledTooltip;

    /**
     * @var callable|null
     */
    private $confirmMessageCallback;

    /**
     * @var string|null
     */
    private $icon;

    /**
     * @var string|null
     */
    private $disabledIcon;

    /**
     * @var bool
     */
    private $isModal = false;

    public function __construct(string $route, array $routeParameters)
    {
        $this->route = $route;
        $this->routeParameters = $routeParameters;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function setRouteParameters(array $routeParameters)
    {
        $this->routeParameters = $routeParameters;
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $title = null)
    {
        $this->title = $title;
    }

    public function getCssClasses(): array
    {
        return $this->cssClasses;
    }

    public function setCssClasses(array $cssClasses, bool $override = false): void
    {
        $this->cssClasses = $cssClasses;
        $this->cssClassesOverride = $override;
    }

    public function getDisabledCssClasses(): array
    {
        return $this->disabledCssClasses;
    }

    public function setDisabledCssClasses(array $disabledCssClasses, bool $override = false): void
    {
        $this->disabledCssClasses = $disabledCssClasses;
        $this->disabledCssClassesOverride = $override;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function addRenderCondition(callable $callback)
    {
        $this->renderConditions[] = $callback;
    }

    public function getRenderSubstitute(): bool
    {
        return $this->renderSubstitute;
    }

    public function setRenderSubstitute(bool $renderSubstitute): void
    {
        $this->renderSubstitute = $renderSubstitute;
    }

    public function addDisabledCondition(callable $callback)
    {
        $this->disabledConditions[] = $callback;
    }

    public function setDisabledTooltip(string $disabledTooltip = null)
    {
        $this->disabledTooltip = $disabledTooltip;
    }

    /**
     * @return string|null
     */
    public function getConfirmMessage(array $row)
    {
        return $this->confirmMessageCallback ? ($this->confirmMessageCallback)($row) : null;
    }

    public function setConfirmMessageCallback(callable $callback)
    {
        $this->confirmMessageCallback = $callback;
    }

    /**
     * @return string|null
     */
    public function getIcon()
    {
        return $this->icon;
    }

    public function setIcon(string $icon = null)
    {
        $this->icon = $icon;
    }

    public function getDisabledIcon(): ?string
    {
        return $this->disabledIcon;
    }

    public function setDisabledIcon(?string $disabledIcon): void
    {
        $this->disabledIcon = $disabledIcon;
    }

    public function isModal(): bool
    {
        return $this->isModal;
    }

    public function setIsModal(bool $isModal = true)
    {
        $this->isModal = $isModal;
    }

    public function canRender(array $row): bool
    {
        if (empty($this->renderConditions)) {
            return true;
        }

        foreach ($this->renderConditions as $condition) {
            if (! $condition($row)) {
                return false;
            }
        }

        return true;
    }

    public function isDisabled(array $row): bool
    {
        foreach ($this->disabledConditions as $condition) {
            if ($condition($row)) {
                return true;
            }
        }

        return false;
    }

    public function render(
        string $url,
        int $id = null,
        bool $isDisabled = false,
        string $confirmMessage = null
    ): string {
        $a = Html::el('a');

        if ($this->isModal) {
            $a->addAttributes(
                [
                    'href' => '#',
                    'data-modal-url' => $url,
                ]
            );
        } else {
            $a->href($url);
        }

        $defaultClasses = [
            'button',
        ];
        if ($this->icon && ! $this->title) {
            $defaultClasses[] = 'button--icon-only';
        }
        if ($this->cssClassesOverride) {
            $classes = $this->cssClasses;
        } else {
            $classes = array_merge($defaultClasses, $this->cssClasses);
        }

        if ($isDisabled) {
            $a->setName('span');
            if ($this->disabledTooltip) {
                $a->data('tooltip', $this->disabledTooltip);
            }

            if ($this->disabledCssClasses) {
                if ($this->disabledCssClassesOverride) {
                    $classes = $this->disabledCssClasses;
                } else {
                    $classes = array_merge($defaultClasses, $this->disabledCssClasses);
                }
            } else {
                $classes[] = 'is-disabled';
            }
        }

        $a->addAttributes(
            [
                'class' => implode(' ', $classes),
            ]
        );

        foreach ($this->data as $key => $value) {
            if ($key === self::KEY_CONFIRM_MESSAGE) {
                if ($isDisabled) {
                    continue;
                }

                if (null !== $confirmMessage) {
                    $a->data($key, $confirmMessage);
                    continue;
                }
            }

            if ($key === self::KEY_TOOLTIP && $isDisabled && $this->disabledTooltip) {
                continue;
            }

            $a->data($key, $value);
        }

        if ($id) {
            $a->data('id', $id);
        }

        if ($this->icon || ($isDisabled && $this->disabledIcon)) {
            $icon = Html::el(
                'span',
                [
                    'class' => sprintf(
                        'icon %s',
                        $isDisabled && $this->disabledIcon ? $this->disabledIcon : $this->icon
                    ),
                ]
            );
            $a->addHtml($icon);
        }

        if ($this->title) {
            $text = Html::el('span')->setText($this->title);
            if (isset($icon)) {
                $text->setAttribute('class', 'ml-5');
            }
            $a->addHtml($text);
        }

        return (string) $a;
    }

    public function renderSubstitute(): ?string
    {
        if (! $this->renderSubstitute) {
            return null;
        }

        $el = Html::el('span');

        $defaultClasses = [
            'button',
        ];
        if ($this->icon && ! $this->title) {
            $defaultClasses[] = 'button--icon-only';
        }
        if ($this->cssClassesOverride) {
            $classes = $this->cssClasses;
        } else {
            $classes = array_merge($defaultClasses, $this->cssClasses);
        }

        $classes[] = 'button--invisible';

        $el->addAttributes(
            [
                'class' => implode(' ', $classes),
            ]
        );

        if ($this->icon) {
            $icon = Html::el(
                'span',
                [
                    'class' => sprintf(
                        'icon %s',
                        $this->icon
                    ),
                ]
            );
            $el->addHtml($icon);
        }

        if ($this->title) {
            $text = Html::el('span')->setText($this->title);
            if (isset($icon)) {
                $text->setAttribute('class', 'ml-5');
            }
            $el->addHtml($text);
        }

        return (string) $el;
    }
}
