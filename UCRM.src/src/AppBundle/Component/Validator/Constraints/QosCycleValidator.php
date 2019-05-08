<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device;
use Doctrine\Common\Collections\Collection;
use Ds\Queue;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class QosCycleValidator extends ConstraintValidator
{
    /**
     * @param Collection|Device[] $value
     */
    public function validate($value, Constraint $constraint)
    {
        if (! $constraint instanceof QosCycle) {
            throw new UnexpectedTypeException($constraint, QosCycle::class);
        }

        /** @var Device $validatedDevice */
        $validatedDevice = $this->context->getObject();
        if ($validatedDevice->getQosEnabled() !== BaseDevice::QOS_ANOTHER) {
            return;
        }

        $violations = [];

        foreach ($value as $linkedDevice) {
            $queue = new Queue();
            $queue->push(...$linkedDevice->getQosDevices()->toArray());

            while (! $queue->isEmpty()) {
                /** @var Device $device */
                $device = $queue->pop();

                if ($device->getQosDevices()->contains($validatedDevice)) {
                    // break if cycle with this device was already detected (by different path)
                    if (in_array($linkedDevice->getNameWithSite(), $violations, true)) {
                        break;
                    }

                    $this->context
                        ->buildViolation($constraint->message)
                        ->setParameter('%device%', $linkedDevice->getNameWithSite())
                        ->addViolation();
                    $violations[] = $linkedDevice->getNameWithSite();
                    break;
                }

                $queue->push(...$device->getQosDevices()->toArray());
            }
        }
    }
}
