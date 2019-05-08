<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Builder;

use AppBundle\Component\Import\Transformer\ConstraintViolationTransformer;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\Import\ClientImportItemValidationErrors;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ClientImportItemValidationErrorsBuilder implements ImportItemValidationErrorsBuilderInterface
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
     * @var ClientImportItemValidationErrors
     */
    private $validationErrors;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        ConstraintViolationTransformer $constraintViolationTransformer
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->constraintViolationTransformer = $constraintViolationTransformer;
        $this->validationErrors = new ClientImportItemValidationErrors();
    }

    public function getValidationErrors(): ClientImportItemValidationErrors
    {
        return $this->validationErrors;
    }

    protected function getValidationFieldsDifference(?string $rootClass): array
    {
        if ($rootClass === ClientContact::class) {
            return [
                'email' => 'emails',
                'phone' => 'phones',
            ];
        }

        return [];
    }
}
