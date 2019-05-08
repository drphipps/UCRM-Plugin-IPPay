<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid\Column;

class TwigFilterColumn extends BaseColumn implements SortableColumnInterface
{
    use SortableTrait;

    /**
     * @var string
     */
    private $filter;

    /**
     * @var array
     */
    private $filterParameters;

    public function __construct(
        string $name,
        ?string $queryIdentifier,
        string $title,
        string $filter,
        array $filterParameters
    ) {
        parent::__construct($name, $queryIdentifier, $title);

        $this->filter = $filter;
        $this->filterParameters = $filterParameters;
    }

    /**
     * {@inheritdoc}
     */
    public function render($value)
    {
        return $value;
    }

    public function getFilter(): string
    {
        return $this->filter;
    }

    public function getFilterParameters(): array
    {
        return $this->filterParameters;
    }
}
