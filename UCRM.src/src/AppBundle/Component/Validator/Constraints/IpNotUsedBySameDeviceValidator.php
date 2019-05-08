<?php

declare(strict_types=1);

/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\Entity\DeviceInterfaceIp;
use AppBundle\Entity\IpRange as IpRangeEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IpNotUsedBySameDeviceValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (! $constraint instanceof IpNotUsedBySameDevice) {
            throw new UnexpectedTypeException($constraint, IpNotUsedBySameDevice::class);
        }

        if (null === $value) {
            return;
        }

        if (! $value instanceof IpRangeEntity) {
            throw new UnexpectedTypeException($value, IpRangeEntity::class);
        }

        if (null === $value->getIpAddress()) {
            return;
        }

        $deviceInterfaceIp = $this->context->getObject();

        if (! $deviceInterfaceIp instanceof DeviceInterfaceIp) {
            throw new UnexpectedTypeException($value, DeviceInterfaceIp::class);
        }

        $interfaces = $deviceInterfaceIp->getInterface()->getDevice()->getNotDeletedInterfaces();

        foreach ($interfaces as $interface) {
            foreach ($interface->getInterfaceIps() as $interfaceIp) {
                if ($interfaceIp === $deviceInterfaceIp) {
                    continue;
                }

                if (
                    $value->getFirstIp() <= $interfaceIp->getIpRange()->getLastIp()
                    && $value->getLastIp() >= $interfaceIp->getIpRange()->getFirstIp()
                ) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ value }}', $this->formatValue($value->getRangeForView()))
                        ->setParameter(
                            '{{ conflictIp }}',
                            $this->formatValue($interfaceIp->getIpRange()->getRangeForView())
                        )
                        ->addViolation();
                }
            }
        }
    }
}
