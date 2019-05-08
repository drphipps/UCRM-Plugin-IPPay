<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid\Column;

interface SortableColumnInterface
{
    public function isSortable(): bool;

    public function setSortable(bool $sortable);

    public function setOrderByCallback(?callable $callback);

    public function getOrderByCallback(): ?callable;
}
