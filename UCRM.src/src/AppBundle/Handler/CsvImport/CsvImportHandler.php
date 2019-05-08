<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Handler\CsvImport;

use AppBundle\Component\Import\CsvImporter;
use AppBundle\Entity\CsvImportStructure;
use AppBundle\Facade\CsvImportStructureFacade;
use AppBundle\Form\Data\CsvUploadData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @deprecated Client CSV import was refactored, this class is obsolete and only used for Payment import, which is not yet refactored
 * @see https://ubnt.myjetbrains.com/youtrack/issue/UCRM-2807
 */
class CsvImportHandler
{
    /**
     * @var CsvImportStructureFacade
     */
    private $csvImportStructureFacade;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        CsvImportStructureFacade $csvImportStructureFacade,
        EntityManagerInterface $entityManager
    ) {
        $this->csvImportStructureFacade = $csvImportStructureFacade;
        $this->entityManager = $entityManager;
    }

    /**
     * @deprecated
     */
    public function getCsvStructure(CsvUploadData $csvUploadData): array
    {
        $hash = sha1($csvUploadData->file->openFile('r')->fgets());
        $csvImportStructure = $this->entityManager->getRepository(CsvImportStructure::class)->findOneBy(
            [
                'hash' => $hash,
            ]
        );
        if (! $csvImportStructure) {
            $csvImportStructure = new CsvImportStructure();
            $csvImportStructure->setHash($hash);
        }

        $setStructure = [
            CsvImporter::FIELD_HAS_HEADER => $csvUploadData->hasHeader,
            CsvImporter::FIELD_AUTO_DETECT_STRUCTURE => $csvUploadData->autoDetectStructure,
        ];
        if (! $csvUploadData->autoDetectStructure) {
            // in case auto-detect is off, we always save custom structure
            $csvImportStructure->setCsvDelimiter($csvUploadData->delimiter);
            $csvImportStructure->setCsvEnclosure($csvUploadData->enclosure);
            $csvImportStructure->setCsvEscape($csvUploadData->escape);

            if ($csvImportStructure->getId()) {
                $this->csvImportStructureFacade->handleEdit($csvImportStructure);
            } else {
                $this->csvImportStructureFacade->handleNew($csvImportStructure);
            }
        } elseif (! $csvImportStructure->getId()) {
            // if we want auto-detect, but have nothing saved, set it to object, but do NOT save to db
            $csvImportStructure->setCsvDelimiter($csvUploadData->delimiter);
            $csvImportStructure->setCsvEnclosure($csvUploadData->enclosure);
            $csvImportStructure->setCsvEscape($csvUploadData->escape);
        } else {
            // if we want auto-detect and have structure saved, use it
            $setStructure[CsvImporter::FIELD_AUTO_DETECT_STRUCTURE] = false;
        }

        return array_merge(
            $setStructure,
            [
                CsvImporter::FIELD_DELIMITER => $csvImportStructure->getCsvDelimiter(),
                CsvImporter::FIELD_ENCLOSURE => $csvImportStructure->getCsvEnclosure(),
                CsvImporter::FIELD_ESCAPE => $csvImportStructure->getCsvEscape(),
            ]
        );
    }
}
