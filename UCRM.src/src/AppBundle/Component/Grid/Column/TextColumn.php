<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid\Column;

class TextColumn extends BaseColumn implements SortableColumnInterface
{
    use SortableTrait;

    /**
     * @var array
     */
    protected $replacements = [];

    public function getReplacements(): array
    {
        return $this->replacements;
    }

    public function setReplacements(array $replacements)
    {
        $this->replacements = $replacements;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render($value): string
    {
        if ($this->replacements && array_key_exists($value, $this->replacements)) {
            $value = $this->replacements[$value];
        }

        return htmlspecialchars((string) $value, ENT_QUOTES) ?: self::EMPTY_COLUMN;
    }
}
