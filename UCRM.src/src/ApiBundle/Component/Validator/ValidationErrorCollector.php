<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Component\Validator;

class ValidationErrorCollector
{
    /**
     * @var array
     */
    private $errors;

    public function add(string $property, string $message): void
    {
        $this->errors[$property][] = $message;
    }

    public function throwErrors(?string $message = null): void
    {
        if ($this->errors) {
            throw new ValidationHttpException($this->errors, $message);
        }
    }
}
