<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Transformer;

use AppBundle\Component\Import\Exception\ImportException;
use AppBundle\Component\Import\FileManager\ImportFileManager;
use AppBundle\Entity\Import\ImportInterface;
use Generator;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class CsvToMappedArrayTransformer
{
    /**
     * @var ImportFileManager
     */
    private $importFileManager;

    public function __construct(ImportFileManager $importFileManager)
    {
        $this->importFileManager = $importFileManager;
    }

    /**
     * @throws ImportException
     *
     * @return mixed[][]
     */
    public function transform(ImportInterface $import): Generator
    {
        if (! $import->getCsvMapping()) {
            throw new ImportException('Import CSV mapping is required for this transformation.');
        }

        try {
            $file = $this->importFileManager->get($import);
            $file->setCsvControl($import->getCsvDelimiter(), $import->getCsvEnclosure(), $import->getCsvEscape());
        } catch (FileNotFoundException $exception) {
            throw new ImportException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $file->setFlags(
            \SplFileObject::DROP_NEW_LINE
            | \SplFileObject::READ_AHEAD
            | \SplFileObject::SKIP_EMPTY
            | \SplFileObject::READ_CSV
        );

        $skippedHeader = false;
        foreach ($file as $row) {
            if (! $skippedHeader && $import->isCsvHasHeader()) {
                $skippedHeader = true;

                continue;
            }

            yield $this->transformRow($import->getCsvMapping(), $row);
        }
    }

    private function transformRow(array $mapping, array $row): array
    {
        $item = [];

        foreach ($mapping as $key => $rowKey) {
            $item[$key] = $row[$rowKey] ?? null;
        }

        return $item;
    }
}
