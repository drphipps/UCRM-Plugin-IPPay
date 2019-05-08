<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Validator;

use AppBundle\Component\Import\Builder\ClientErrorSummaryBuilderFactory;
use AppBundle\Entity\Import\ClientErrorSummary;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ClientImportItemValidationErrors;
use AppBundle\Entity\Import\ServiceImportItemValidationErrors;
use Doctrine\ORM\EntityManagerInterface;
use Generator;

class ClientImportValidator
{
    /**
     * @var ClientImportItemValidator
     */
    private $clientImportItemValidator;

    /**
     * @var ClientErrorSummaryBuilderFactory
     */
    private $errorSummaryBuilderFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ClientImportItemValidator $clientImportItemValidator,
        ClientErrorSummaryBuilderFactory $errorSummaryBuilderFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->clientImportItemValidator = $clientImportItemValidator;
        $this->errorSummaryBuilderFactory = $errorSummaryBuilderFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * @return ClientErrorSummary[]|ClientImportItemValidationErrors[]|ServiceImportItemValidationErrors[]
     */
    public function validate(ClientImport $import): Generator
    {
        $errorSummaryBuilder = $this->errorSummaryBuilderFactory->create();

        foreach ($import->getItems() as $item) {
            yield from $this->clientImportItemValidator->validate($item, $errorSummaryBuilder);
        }

        $errorSummary = $errorSummaryBuilder->getClientErrorSummary();
        $errorSummary->setImport($this->entityManager->getReference(ClientImport::class, $import->getId()));
        $import->setErrorSummary($this->entityManager->getReference(ClientErrorSummary::class, $errorSummary->getId()));

        yield $errorSummary;
    }
}
