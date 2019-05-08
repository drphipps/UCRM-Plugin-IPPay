<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid\Column;

class RawCustomColumn extends CustomColumn
{
    /**
     * {@inheritdoc}
     */
    public function render($row): string
    {
        return (string) ($this->renderCallback)($row);
    }
}
