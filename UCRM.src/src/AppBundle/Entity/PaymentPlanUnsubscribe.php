<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 *
 * @deprecated @todo Can be safely deleted in the future when everyone is on 2.11.0.
 * @see \AppBundle\Command\Migration\MoveQueuesToRabbitCommand
 */
class PaymentPlanUnsubscribe
{
    /**
     * @var PaymentPlan
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\PaymentPlan")
     * @ORM\JoinColumn(name="payment_plan_id", referencedColumnName="payment_plan_id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    protected $paymentPlan;

    public function __construct(PaymentPlan $paymentPlan)
    {
        $this->paymentPlan = $paymentPlan;
    }

    /**
     * @return PaymentPlanUnsubscribe
     */
    public function setPaymentPlan(PaymentPlan $paymentPlan)
    {
        $this->paymentPlan = $paymentPlan;

        return $this;
    }

    /**
     * @return PaymentPlan
     */
    public function getPaymentPlan()
    {
        return $this->paymentPlan;
    }
}
