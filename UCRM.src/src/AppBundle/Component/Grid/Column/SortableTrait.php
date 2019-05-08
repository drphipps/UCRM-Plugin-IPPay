<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid\Column;

trait SortableTrait
{
    /**
     * @var bool
     */
    protected $sortable = false;

    /**
     * @var callable|null
     */
    protected $orderByCallback;

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function getOrderByCallback(): ?callable
    {
        return $this->orderByCallback;
    }

    public function setOrderByCallback(?callable $orderByCallback): self
    {
        $this->orderByCallback = $orderByCallback;

        return $this;
    }
}
