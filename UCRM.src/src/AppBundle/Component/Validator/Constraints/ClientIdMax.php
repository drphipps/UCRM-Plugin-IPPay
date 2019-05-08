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
class ClientIdMax extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Value {{ value }} is lower or equal than max client ID {{ nextClientId }}.';
}
