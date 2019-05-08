<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\DataProvider\ClientDataProvider;
use AppBundle\Entity\Client;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ClientCanBeConvertedToLeadValidator extends ConstraintValidator
{
    /**
     * @var ClientDataProvider
     */
    private $clientDataProvider;

    public function __construct(ClientDataProvider $clientDataProvider)
    {
        $this->clientDataProvider = $clientDataProvider;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (! $constraint instanceof ClientCanBeConvertedToLead) {
            throw new UnexpectedTypeException($constraint, ClientCanBeConvertedToLead::class);
        }

        // if it's null or false, we don't care as it's not conversion to lead
        if (! $value) {
            return;
        }

        $client = $this->context->getObject();
        if (! $client instanceof Client) {
            throw new UnexpectedTypeException($client, Client::class);
        }

        if (! $this->clientDataProvider->canBeConvertedToLead($client)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
