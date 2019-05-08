<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Transformer;

use AppBundle\Component\Import\DataProvider\CsvColumnDataProvider;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

abstract class AbstractCsvRowToImportItemTransformer
{
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var CsvColumnDataProvider
     */
    private $csvColumnDataProvider;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        CsvColumnDataProvider $csvColumnDataProvider
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->csvColumnDataProvider = $csvColumnDataProvider;
    }

    /**
     * @return object|null
     */
    protected function transformToItem(array $row, string $itemClass)
    {
        $item = new $itemClass();
        $reflectionClass = new \ReflectionClass($itemClass);

        $isEmpty = true;
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $csvColumn = $this->csvColumnDataProvider->getCsvColumn($reflectionProperty);
            if (! $csvColumn) {
                continue;
            }

            $value = array_key_exists($csvColumn->csvMappingField, $row)
                ? trim((string) $row[$csvColumn->csvMappingField])
                : '';

            if ($value === '') {
                continue;
            }

            $this->propertyAccessor->setValue(
                $item,
                $reflectionProperty->getName(),
                $value
            );
            $isEmpty = false;
        }

        return $isEmpty ? null : $item;
    }
}
