<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class ClientCanBeConvertedToLead extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Client can be only converted to lead if there are no invoices, payments, refunds or unquoted services.';
}
