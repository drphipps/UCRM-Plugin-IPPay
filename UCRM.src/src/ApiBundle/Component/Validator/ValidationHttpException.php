<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Component\Validator;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ValidationHttpException extends HttpException
{
    /**
     * @var array
     */
    private $validationErrorsFlattened;

    public function __construct(array $validationErrorsFlattened, string $message = null)
    {
        if (null === $message) {
            $message = 'Validation failed.';
        }

        parent::__construct(422, $message);
        $this->validationErrorsFlattened = $validationErrorsFlattened;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrorsFlattened;
    }
}
