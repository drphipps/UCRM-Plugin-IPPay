<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid\Column;

class CustomColumn extends BaseColumn implements SortableColumnInterface
{
    use SortableTrait;

    /**
     * @var callable
     */
    protected $renderCallback;

    public function __construct($name, $title, $renderCallback)
    {
        parent::__construct($name, null, $title);

        $this->renderCallback = $renderCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function render($row): string
    {
        $result = (string) ($this->renderCallback)($row);

        return $result === self::EMPTY_COLUMN
            ? $result
            : htmlspecialchars($result ?? '', ENT_QUOTES);
    }
}
