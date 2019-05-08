<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Validator;

use AppBundle\Component\Import\Builder\ClientErrorSummaryBuilder;
use AppBundle\Component\Import\Builder\ServiceImportItemValidationErrorsBuilderFactory;
use AppBundle\Component\Import\DataProvider\TransformerEntityData;
use AppBundle\Component\Import\Transformer\ServiceImportItemToServiceTransformer;
use AppBundle\Entity\Client;
use AppBundle\Entity\Import\ServiceImportItem;
use AppBundle\Entity\Import\ServiceImportItemValidationErrors;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ServiceImportItemValidator
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ServiceImportItemValidationErrorsBuilderFactory
     */
    private $validationErrorsBuilderFactory;

    /**
     * @var ServiceImportItemToServiceTransformer
     */
    private $itemToServiceTransformer;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ValidatorInterface $validator,
        ServiceImportItemValidationErrorsBuilderFactory $validationErrorsBuilderFactory,
        ServiceImportItemToServiceTransformer $itemToServiceTransformer,
        EntityManagerInterface $entityManager
    ) {
        $this->validator = $validator;
        $this->validationErrorsBuilderFactory = $validationErrorsBuilderFactory;
        $this->itemToServiceTransformer = $itemToServiceTransformer;
        $this->entityManager = $entityManager;
    }

    /**
     * @return ServiceImportItemValidationErrors[]
     */
    public function validate(
        ServiceImportItem $item,
        Client $client,
        TransformerEntityData $transformerEntityData,
        ClientErrorSummaryBuilder $errorSummaryBuilder
    ): Generator {
        $validationErrorsBuilder = $this->validationErrorsBuilderFactory->create();

        $service = $this->itemToServiceTransformer->transform(
            $item,
            $client,
            $transformerEntityData,
            $validationErrorsBuilder,
            $errorSummaryBuilder
        );

        $validationErrorsBuilder->addViolationList($this->validator->validate($service));

        $validationErrors = $validationErrorsBuilder->getValidationErrors();
        if ($validationErrors->hasErrors()) {
            $validationErrors->setServiceImportItem(
                $this->entityManager->getReference(ServiceImportItem::class, $item->getId())
            );
            $item->setValidationErrors(
                $this->entityManager->getReference(ServiceImportItemValidationErrors::class, $validationErrors->getId())
            );
            $errorSummaryBuilder->addValidationErrors($validationErrors);

            yield $validationErrors;
        } else {
            $item->setValidationErrors(null);
        }
    }
}
