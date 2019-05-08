<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade\Exception;

use AppBundle\Entity\PaymentPlan;

class CannotCancelClientSubscriptionException extends \Exception implements ClientNotDeletedExceptionInterface
{
    /**
     * @var PaymentPlan
     */
    private $paymentPlan;

    public function __construct(PaymentPlan $paymentPlan, $message, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->paymentPlan = $paymentPlan;
    }

    public function getPaymentPlan(): PaymentPlan
    {
        return $this->paymentPlan;
    }
}
