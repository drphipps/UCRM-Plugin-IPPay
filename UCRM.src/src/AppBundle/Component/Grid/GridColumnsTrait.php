<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Column\CustomColumn;
use AppBundle\Component\Grid\Column\RawCustomColumn;
use AppBundle\Component\Grid\Column\RawTextColumn;
use AppBundle\Component\Grid\Column\TextColumn;
use AppBundle\Component\Grid\Column\TwigFilterColumn;

trait GridColumnsTrait
{
    /**
     * @var array|BaseColumn[]
     */
    private $columns = [];

    /**
     * @return BaseColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(string $columnName): ?BaseColumn
    {
        foreach ($this->columns as $column) {
            if ($columnName === $column->getName()) {
                return $column;
            }
        }

        return null;
    }

    public function addTextColumn(string $name, string $identifier, string $title): TextColumn
    {
        return $this->columns[] = new TextColumn($name, $identifier, $title);
    }

    public function addRawTextColumn(string $name, string $identifier, string $title): RawTextColumn
    {
        return $this->columns[] = new RawTextColumn($name, $identifier, $title);
    }

    public function addCustomColumn(string $name, string $title, callable $renderCallback): CustomColumn
    {
        return $this->columns[] = new CustomColumn(
            $name,
            $title,
            $renderCallback
        );
    }

    public function addRawCustomColumn(string $name, string $title, callable $renderCallback): RawCustomColumn
    {
        return $this->columns[] = new RawCustomColumn(
            $name,
            $title,
            $renderCallback
        );
    }

    public function addTwigFilterColumn(
        string $name,
        string $identifier,
        string $title,
        string $filter,
        array $filterParameters = []
    ): TwigFilterColumn {
        return $this->columns[] = new TwigFilterColumn(
            $name,
            $identifier,
            $title,
            $filter,
            $filterParameters
        );
    }
}
