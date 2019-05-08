<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Builder;

use AppBundle\Component\Import\Transformer\ConstraintViolationTransformer;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ClientImportItemValidationErrorsBuilderFactory
{
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var ConstraintViolationTransformer
     */
    private $constraintViolationTransformer;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        ConstraintViolationTransformer $constraintViolationTransformer
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->constraintViolationTransformer = $constraintViolationTransformer;
    }

    public function create(): ClientImportItemValidationErrorsBuilder
    {
        return new ClientImportItemValidationErrorsBuilder(
            $this->propertyAccessor,
            $this->constraintViolationTransformer
        );
    }
}
