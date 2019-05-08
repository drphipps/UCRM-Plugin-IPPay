<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\DataProvider;

use AppBundle\Component\Import\Annotation\CsvColumn;
use Doctrine\Common\Annotations\Reader;

class CsvColumnDataProvider
{
    /**
     * @var Reader
     */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @return CsvColumn[]
     */
    public function getCsvColumns(\ReflectionClass $reflectionClass): array
    {
        $csvColumns = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $csvColumns[] = $this->getCsvColumn($property);
        }

        return array_filter($csvColumns);
    }

    public function getCsvColumn(\ReflectionProperty $reflectionProperty): ?CsvColumn
    {
        $csvColumn = $this->reader->getPropertyAnnotation($reflectionProperty, CsvColumn::class);

        return $csvColumn instanceof CsvColumn
            ? $csvColumn
            : null;
    }
}
