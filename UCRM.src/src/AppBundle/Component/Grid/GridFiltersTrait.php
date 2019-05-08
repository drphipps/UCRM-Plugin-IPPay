<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid;

use AppBundle\Component\Grid\Filter\BaseFilterField;
use AppBundle\Component\Grid\Filter\BoolFilterField;
use AppBundle\Component\Grid\Filter\DateFilterField;
use AppBundle\Component\Grid\Filter\ElasticFilterField;
use AppBundle\Component\Grid\Filter\MultipleSelectFilterField;
use AppBundle\Component\Grid\Filter\NullBoolFilterField;
use AppBundle\Component\Grid\Filter\NumberRangeFilter;
use AppBundle\Component\Grid\Filter\RadioFilterField;
use AppBundle\Component\Grid\Filter\SelectFilterField;
use AppBundle\Component\Grid\Filter\TextFilterField;

trait GridFiltersTrait
{
    /**
     * @var array|BaseFilterField[]
     */
    private $filters = [];

    public function addElasticFilter(
        string $name,
        string $queryIdentifier,
        string $title,
        string $entityName,
        string $placeholder = ''
    ): ElasticFilterField {
        return $this->filters[$name] = new ElasticFilterField(
            $this,
            $name,
            $queryIdentifier,
            $title,
            $entityName,
            $placeholder
        );
    }

    public function addTextFilter(string $name, string $queryIdentifier, string $title): TextFilterField
    {
        return $this->filters[$name] = new TextFilterField(
            $this,
            $name,
            $queryIdentifier,
            $this->translator->trans($title)
        );
    }

    public function addDateFilter(
        string $name,
        string $queryIdentifier,
        string $title,
        bool $range = false
    ): DateFilterField {
        return $this->filters[$name] = new DateFilterField(
            $this,
            $name,
            $queryIdentifier,
            $this->translator->trans($title),
            $range
        );
    }

    public function addNumberRangeFilter(string $name, string $queryIdentifier, string $title): NumberRangeFilter
    {
        return $this->filters[$name] = new NumberRangeFilter(
            $this,
            $name,
            $queryIdentifier,
            $this->translator->trans($title)
        );
    }

    public function addSelectFilter(
        string $name,
        string $queryIdentifier,
        string $title,
        array $options = [],
        bool $allowSearch = false
    ): SelectFilterField {
        return $this->filters[$name] = new SelectFilterField(
            $this,
            $name,
            $queryIdentifier,
            $this->translator->trans($title),
            $options,
            '',
            '',
            $allowSearch
        );
    }

    public function addMultipleSelectFilter(
        string $name,
        string $queryIdentifier,
        string $title,
        array $options = [],
        string $placeholder = ''
    ): MultipleSelectFilterField {
        return $this->filters[$name] = new MultipleSelectFilterField(
            $this,
            $name,
            $queryIdentifier,
            $this->translator->trans($title),
            $options,
            '',
            $placeholder
        );
    }

    public function addBoolFilter(string $name, string $queryIdentifier, string $title): BoolFilterField
    {
        return $this->filters[$name] = new BoolFilterField(
            $this,
            $name,
            $queryIdentifier,
            $this->translator->trans($title)
        );
    }

    public function addNullBoolFilter(string $name, string $queryIdentifier, string $title): NullBoolFilterField
    {
        return $this->filters[$name] = new NullBoolFilterField(
            $this,
            $name,
            $queryIdentifier,
            $this->translator->trans($title)
        );
    }

    public function addRadioFilter(
        string $name,
        string $queryIdentifier,
        string $title,
        array $options = [],
        $nullFilter = false
    ): RadioFilterField {
        $this->filters[$name] = new RadioFilterField(
            $this,
            $name,
            $queryIdentifier,
            $this->translator->trans($title),
            $options
        );
        $this->filters[$name]->setIsNullFilter($nullFilter);

        return $this->filters[$name];
    }

    /**
     * @return BaseFilterField[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getFilter(string $name): BaseFilterField
    {
        if (! array_key_exists($name, $this->filters)) {
            throw new \InvalidArgumentException(
                sprintf('This filter (%s) does not exist.', $name)
            );
        }

        return $this->filters[$name];
    }
}
