<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Loader;

use AppBundle\Component\Import\Transformer\CsvRowToClientImportItemTransformer;
use AppBundle\Component\Import\Transformer\CsvRowToServiceImportItemTransformer;
use AppBundle\Component\Import\Transformer\CsvToMappedArrayTransformer;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ServiceImportItem;
use Doctrine\ORM\EntityManagerInterface;
use Generator;

class ClientImportItemsLoader
{
    /**
     * @var CsvToMappedArrayTransformer
     */
    private $csvToMappedArrayTransformer;

    /**
     * @var CsvRowToClientImportItemTransformer
     */
    private $csvRowToClientImportItemTransformer;

    /**
     * @var CsvRowToServiceImportItemTransformer
     */
    private $csvRowToServiceImportItemTransformer;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        CsvToMappedArrayTransformer $csvToMappedArrayTransformer,
        CsvRowToClientImportItemTransformer $csvRowToClientImportItemTransformer,
        CsvRowToServiceImportItemTransformer $csvRowToServiceImportItemTransformer,
        EntityManagerInterface $entityManager
    ) {
        $this->csvToMappedArrayTransformer = $csvToMappedArrayTransformer;
        $this->csvRowToClientImportItemTransformer = $csvRowToClientImportItemTransformer;
        $this->csvRowToServiceImportItemTransformer = $csvRowToServiceImportItemTransformer;
        $this->entityManager = $entityManager;
    }

    /**
     * @return ClientImportItem[]|ServiceImportItem[]
     */
    public function load(ClientImport $clientImport): Generator
    {
        $data = $this->csvToMappedArrayTransformer->transform($clientImport);

        // if CSV has header, line number will start as #2
        $lineNumber = (int) $clientImport->isCsvHasHeader();
        $previousClientItem = null;
        foreach ($data as $row) {
            ++$lineNumber;

            $clientItem = $this->csvRowToClientImportItemTransformer->transform($row);
            // Empty Client data indicate, there should be service added to previous row's client.
            // If there is no Client nor Service data, the row is skipped.
            // If there is Service data, but no Client data (including previous row's),
            // the empty Client data is saved, so that we can show validation error.
            if ($clientItem->isEmpty() && $previousClientItem) {
                $clientItem = $previousClientItem;
            } else {
                $clientItem->setLineNumber($lineNumber);
                $clientItem->setImport($this->entityManager->getReference(ClientImport::class, $clientImport->getId()));
                $previousClientItem = $clientItem;

                yield $clientItem;
            }

            $serviceItem = $this->csvRowToServiceImportItemTransformer->transform($row);
            if ($serviceItem) {
                $serviceItem->setLineNumber($lineNumber);
                $serviceItem->setImportItem(
                    $this->entityManager->getReference(ClientImportItem::class, $clientItem->getId())
                );

                yield $serviceItem;
            }
        }
    }
}
