<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\Entity\IpRange as IpRangeEntity;
use AppBundle\Entity\ServiceIp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IpNotUsedByServiceValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (! $constraint instanceof IpNotUsedByService) {
            throw new UnexpectedTypeException($constraint, IpNotUsedByService::class);
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

        $overlappingCount = $this->entityManager
            ->getRepository(ServiceIp::class)
            ->getOverlappingRangesCount(
                $value->getFirstIp(),
                $value->getLastIp(),
                $serviceIp->getId()
            );

        if ($overlappingCount > 0) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value->getRangeForView()))
                ->addViolation();
        }
    }
}
