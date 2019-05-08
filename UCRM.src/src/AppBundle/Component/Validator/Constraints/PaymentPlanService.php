<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PaymentPlanService extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Selected service already has active subscription.';
}
