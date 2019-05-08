<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\DataProvider;

use AppBundle\Component\Import\FileManager\ImportFileManager;
use AppBundle\Component\Import\Mapping\ClientMappingGuesser;
use AppBundle\Entity\CsvImportMapping;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Entity\Organization;
use AppBundle\Form\Data\CsvMappingData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class ImportDataProvider
{
    /**
     * @var ImportFileManager
     */
    private $importFileManager;

    /**
     * @var ClientMappingGuesser
     */
    private $clientMappingGuesser;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ImportFileManager $importFileManager,
        ClientMappingGuesser $clientMappingGuesser,
        EntityManagerInterface $entityManager
    ) {
        $this->importFileManager = $importFileManager;
        $this->clientMappingGuesser = $clientMappingGuesser;
        $this->entityManager = $entityManager;
    }

    public function getCsvFields(ImportInterface $import): array
    {
        try {
            $file = $this->importFileManager->get($import);
            $file->setCsvControl($import->getCsvDelimiter(), $import->getCsvEnclosure(), $import->getCsvEscape());
        } catch (FileNotFoundException $exception) {
            return [];
        }

        return array_filter($file->fgetcsv() ?: []);
    }

    public function getDefaultCsvMappingData(
        ImportInterface $import,
        array $fields,
        ?CsvImportMapping $csvImportMapping
    ): CsvMappingData {
        $csvMappingData = new CsvMappingData();
        $csvMappingData->organization = $import->getOrganization()
            ?? $this->entityManager->getRepository(Organization::class)->getSelectedOrAlone();

        if (! $import->getCsvMapping()) {
            $import->setCsvMapping(
                $csvImportMapping
                    ? $csvImportMapping->getMapping()
                    : $this->guessCsvMapping($import, $fields)
            );
        }

        $csvMappingData->mapping = $import->getCsvMapping();

        return $csvMappingData;
    }

    private function guessCsvMapping(ImportInterface $import, array $fields): array
    {
        switch (true) {
            case $import instanceof ClientImport:
                return $this->clientMappingGuesser->guess($fields);
            default:
                throw new \InvalidArgumentException(
                    sprintf('Not supported for "%s".', get_class($import))
                );
        }
    }
}
