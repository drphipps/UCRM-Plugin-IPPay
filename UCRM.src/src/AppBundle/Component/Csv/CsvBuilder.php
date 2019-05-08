<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Csv;

class CsvBuilder
{
    private const FORMULAE_SIGNS = [
        '+',
        '-',
        '=',
        '@',
    ];

    /**
     * @var array
     */
    private $columns = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var bool
     */
    private $includeHeaderRow = true;

    public function setIncludeHeaderRow(bool $includeHeaderRow): void
    {
        $this->includeHeaderRow = $includeHeaderRow;
    }

    public function addData(array $data): void
    {
        $dataRow = array_fill_keys($this->columns, null);
        foreach ($data as $columnName => $value) {
            if (! in_array($columnName, $this->columns, true)) {
                $this->addColumn($columnName);
            }

            $dataRow[$columnName] = $value;
        }

        $this->data[] = $dataRow;
    }

    public function resetData(): void
    {
        $this->data = [];
    }

    public function getCsv(): string
    {
        if (null === $this->columns) {
            throw new \RuntimeException('Add data first.');
        }

        $fp = fopen('php://temp', 'w+');
        assert($fp);

        $data = $this->includeHeaderRow
            ? array_merge(
                [
                    $this->columns,
                ],
                $this->data
            )
            : $this->data;

        foreach ($data as $dataRow) {
            // The \0 is needed because of https://bugs.php.net/bug.php?id=43225
            fputcsv($fp, $this->sanitizeDataRow($dataRow), ',', '"', "\0");
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }

    /**
     * @see https://www.contextis.com/resources/blog/comma-separated-vulnerabilities/
     */
    private function sanitizeDataRow(array $dataRow): array
    {
        foreach ($dataRow as $key => $value) {
            $value = ltrim((string) $value);
            if ($value !== '' && in_array($value[0], self::FORMULAE_SIGNS, true)) {
                $dataRow[$key] = '\'' . $value;
            }
        }

        return $dataRow;
    }

    private function addColumn(string $columnName): void
    {
        $this->columns[] = $columnName;
        foreach ($this->data as $key => $row) {
            $this->data[$key][$columnName] = null;
        }
    }
}
