<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid\Column;

class RawTextColumn extends TextColumn
{
    /**
     * {@inheritdoc}
     */
    public function render($value): string
    {
        if ($this->replacements && array_key_exists($value, $this->replacements)) {
            $value = $this->replacements[$value];
        }

        return (string) $value ?: self::EMPTY_COLUMN;
    }
}
