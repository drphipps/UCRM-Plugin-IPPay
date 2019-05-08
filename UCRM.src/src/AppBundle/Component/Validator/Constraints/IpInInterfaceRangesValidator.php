<?php

declare(strict_types=1);

/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\IpRange as IpRangeEntity;
use AppBundle\Entity\ServiceIp;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IpInInterfaceRangesValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (! $constraint instanceof IpInInterfaceRanges) {
            throw new UnexpectedTypeException($constraint, IpInInterfaceRanges::class);
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

        $serviceIp = $this->context->getObject();

        if (! $serviceIp instanceof ServiceIp) {
            throw new UnexpectedTypeException($value, ServiceIp::class);
        }

        // Check only the interface of the current ServiceDevice.
        // Other devices can have completely separate IP addresses and they can overlap.
        $interface = $serviceIp
            ->getServiceDevice()
            ->getInterface();

        $rangeFound = false;

        foreach ($interface->getInterfaceIps() as $ip) {
            $ipRange = $ip->getIpRange();

            // If the validated value is a single IP address it should not be the primary IP address
            // for any range of the interface.
            if ($value->getFirstIp() === $value->getLastIp() && $value->getIpAddress() === $ipRange->getIpAddress()) {
                $this->context
                    ->buildViolation($constraint->messageIpUsedOnInterface)
                    ->setParameter('{{ value }}', $this->formatValue($value->getRangeForView()))
                    ->setParameter('{{ interface }}', $this->formatInterface($interface))
                    ->addViolation();
            }

            if ($value->getFirstIp() >= $ipRange->getFirstIp() && $value->getLastIp() <= $ipRange->getLastIp()) {
                // Validated IP address is in the interface range. Disable the error.
                $rangeFound = true;
            }
        }

        if (! $rangeFound) {
            // None of the interfaces ranges contains this IP address.
            $this->context
                ->buildViolation($constraint->messageIpNotInRanges)
                ->setParameter('{{ value }}', $this->formatValue($value->getRangeForView()))
                ->setParameter('{{ interface }}', $this->formatInterface($interface))
                ->addViolation();
        }
    }

    private function formatInterface(DeviceInterface $interface): string
    {
        return $this->formatValue(
            sprintf(
                '%s – %s',
                $interface->getDevice()->getName(),
                $interface->getName()
            )
        );
    }
}
