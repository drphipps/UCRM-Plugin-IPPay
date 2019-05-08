<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid\Column;

abstract class BaseColumn
{
    public const EMPTY_COLUMN = '<hr>';

    /**
     * @var string|null
     */
    protected $queryIdentifier;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var int|null
     */
    protected $width;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $isGrouped = false;

    /**
     * @var bool
     */
    protected $alignRight = false;

    /**
     * @var string
     */
    protected $cssClass = '';

    public function __construct(string $name, ?string $queryIdentifier, string $title)
    {
        $this->name = $name;
        $this->queryIdentifier = $queryIdentifier;
        $this->title = $title;
    }

    /**
     * In case of TwigFilterColumn the argument and value can be anything.
     *
     * @param string|array|mixed $value
     *
     * @return string|mixed
     */
    abstract public function render($value);

    public function getQueryIdentifier(): ?string
    {
        return $this->queryIdentifier;
    }

    public function setQueryIdentifier(?string $queryIdentifier)
    {
        $this->queryIdentifier = $queryIdentifier;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width)
    {
        $this->width = $width;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isGrouped(): bool
    {
        return $this->isGrouped;
    }

    public function setIsGrouped(bool $isGrouped = true)
    {
        $this->isGrouped = $isGrouped;

        return $this;
    }

    public function isAlignRight(): bool
    {
        return $this->alignRight;
    }

    public function setAlignRight(bool $alignRight = true)
    {
        $this->alignRight = $alignRight;

        return $this;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function setCssClass(string $cssClass)
    {
        $this->cssClass = $cssClass;

        return $this;
    }
}
