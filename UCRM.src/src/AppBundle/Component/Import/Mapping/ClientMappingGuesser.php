<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Mapping;

use AppBundle\Component\Import\Annotation\CsvColumn;
use AppBundle\Component\Import\DataProvider\CsvColumnDataProvider;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ServiceImportItem;
use Nette\Utils\Strings;

class ClientMappingGuesser
{
    /**
     * @var CsvColumnDataProvider
     */
    private $csvColumnDataProvider;

    public function __construct(CsvColumnDataProvider $csvColumnDataProvider)
    {
        $this->csvColumnDataProvider = $csvColumnDataProvider;
    }

    public function guess(array $fields): array
    {
        $mapping = [];

        /** @var CsvColumn[] $csvColumns */
        $csvColumns = array_merge(
            $this->csvColumnDataProvider->getCsvColumns(new \ReflectionClass(ClientImportItem::class)),
            $this->csvColumnDataProvider->getCsvColumns(new \ReflectionClass(ServiceImportItem::class))
        );
        foreach ($csvColumns as $csvColumn) {
            $mapping[$csvColumn->csvMappingField] = '';

            foreach ($fields as $key => $columnName) {
                if (in_array($key, $mapping, true)) {
                    continue;
                }

                if (in_array($this->normalizeColumnName($columnName), $csvColumn->automaticRecognition, true)) {
                    $mapping[$csvColumn->csvMappingField] = $key;

                    break;
                }
            }
        }

        return $mapping;
    }

    private function normalizeColumnName(string $columnName)
    {
        $columnName = Strings::replace($columnName, '/\([^)]*\)/', '');
        $columnName = Strings::lower(
            strtr(
                $columnName,
                [
                    '.' => '',
                    '-' => '',
                    '_' => '',
                    ' ' => '',
                ]
            )
        );

        return $columnName;
    }
}
