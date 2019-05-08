<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Builder;

use AppBundle\Component\Import\Transformer\ConstraintViolationTransformer;

class ClientErrorSummaryBuilderFactory
{
    /**
     * @var ConstraintViolationTransformer
     */
    private $constraintViolationTransformer;

    public function __construct(ConstraintViolationTransformer $constraintViolationTransformer)
    {
        $this->constraintViolationTransformer = $constraintViolationTransformer;
    }

    public function create(): ClientErrorSummaryBuilder
    {
        return new ClientErrorSummaryBuilder($this->constraintViolationTransformer);
    }
}
