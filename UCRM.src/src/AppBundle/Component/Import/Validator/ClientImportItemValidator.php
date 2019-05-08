<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Validator;

use AppBundle\Component\Import\Builder\ClientErrorSummaryBuilder;
use AppBundle\Component\Import\Builder\ClientImportItemValidationErrorsBuilderFactory;
use AppBundle\Component\Import\DataProvider\TransformerEntityDataFactory;
use AppBundle\Component\Import\Transformer\ClientImportItemToClientTransformer;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ClientImportItemValidationErrors;
use AppBundle\Entity\Import\ServiceImportItemValidationErrors;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ClientImportItemValidator
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ClientImportItemValidationErrorsBuilderFactory
     */
    private $validationErrorsBuilderFactory;

    /**
     * @var ClientImportItemToClientTransformer
     */
    private $itemToClientTransformer;

    /**
     * @var ServiceImportItemValidator
     */
    private $serviceImportItemValidator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransformerEntityDataFactory
     */
    private $transformerEntityDataFactory;

    public function __construct(
        ValidatorInterface $validator,
        ClientImportItemValidationErrorsBuilderFactory $validationErrorsBuilderFactory,
        ClientImportItemToClientTransformer $itemToClientTransformer,
        ServiceImportItemValidator $serviceImportItemValidator,
        EntityManagerInterface $entityManager,
        TransformerEntityDataFactory $transformerEntityDataFactory
    ) {
        $this->validator = $validator;
        $this->validationErrorsBuilderFactory = $validationErrorsBuilderFactory;
        $this->itemToClientTransformer = $itemToClientTransformer;
        $this->serviceImportItemValidator = $serviceImportItemValidator;
        $this->entityManager = $entityManager;
        $this->transformerEntityDataFactory = $transformerEntityDataFactory;
    }

    /**
     * @return ClientImportItemValidationErrors[]|ServiceImportItemValidationErrors[]
     */
    public function validate(ClientImportItem $item, ClientErrorSummaryBuilder $errorSummaryBuilder): Generator
    {
        $transformerEntityData = $this->transformerEntityDataFactory->create();
        $validationErrorsBuilder = $this->validationErrorsBuilderFactory->create();

        $client = $this->itemToClientTransformer->transform(
            $item,
            $transformerEntityData,
            $validationErrorsBuilder,
            $errorSummaryBuilder
        );

        $validationErrorsBuilder->addViolationList($this->validator->validate($client, null, ['CsvClient']));
        $validationErrorsBuilder->addViolationList($this->validator->validate($client->getUser(), null, ['CsvUser']));
        foreach ($client->getContacts() as $contact) {
            $validationErrorsBuilder->addViolationList(
                $this->validator->validate($contact, null, ['CsvClientContact'])
            );
        }

        $validationErrors = $validationErrorsBuilder->getValidationErrors();
        if ($validationErrors->hasErrors()) {
            $validationErrors->setClientImportItem(
                $this->entityManager->getReference(ClientImportItem::class, $item->getId())
            );
            $item->setHasErrors(true);
            $item->setCanImport(false);
            $errorSummaryBuilder->addValidationErrors($validationErrors);

            yield $validationErrors;
        } else {
            $item->setHasErrors(false);
            $item->setCanImport(true);
            $item->setValidationErrors(null);
        }

        $serviceItemErrorCount = 0;
        foreach ($item->getServiceItems() as $serviceItem) {
            yield from $this->serviceImportItemValidator->validate($serviceItem, $client, $transformerEntityData, $errorSummaryBuilder);

            if ((bool) $serviceItem->getValidationErrors()) {
                ++$serviceItemErrorCount;
            }
        }
        $serviceItemCount = $item->getServiceItems()->count();
        if ($serviceItemErrorCount > 0) {
            $item->setHasErrors(true);

            // If the client is valid, but all services are invalid, it cannot be imported.
            // If at least 1 service is valid, then we can import that.
            if ($serviceItemErrorCount === $serviceItemCount) {
                $item->setCanImport(false);
            }
        }

        if ($item->hasErrors()) {
            $errorSummaryBuilder->increaseErroneousClientCounter();
        }
    }
}
