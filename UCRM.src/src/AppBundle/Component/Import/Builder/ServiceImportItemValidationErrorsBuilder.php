<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Builder;

use AppBundle\Component\Import\Transformer\ConstraintViolationTransformer;
use AppBundle\Entity\Import\ServiceImportItemValidationErrors;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ServiceImportItemValidationErrorsBuilder implements ImportItemValidationErrorsBuilderInterface
{
    use ImportItemValidationErrorsBuilderTrait;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var ConstraintViolationTransformer
     */
    private $constraintViolationTransformer;

    /**
     * @var ServiceImportItemValidationErrors
     */
    private $validationErrors;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        ConstraintViolationTransformer $constraintViolationTransformer
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->constraintViolationTransformer = $constraintViolationTransformer;
        $this->validationErrors = new ServiceImportItemValidationErrors();
    }

    public function getValidationErrors(): ServiceImportItemValidationErrors
    {
        return $this->validationErrors;
    }

    private function getValidationFieldsDifference(?string $rootClass): array
    {
        return [
            'nextInvoicingDayAdjustment' => 'invoicingDaysInAdvance',
            'useCreditAutomatically' => 'invoiceUseCredit',
            'sendEmailsAutomatically' => 'invoiceApproveSendAuto',
            'contractLengthType' => 'contractType',
        ];
    }
}
