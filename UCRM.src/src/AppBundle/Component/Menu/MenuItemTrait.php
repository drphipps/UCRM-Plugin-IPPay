<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

trait MenuItemTrait
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $active = false;

    /**
     * @var string|null
     */
    private $icon;

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }
}
