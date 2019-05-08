<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Transformer;

use AppBundle\Component\Import\Builder\ClientErrorSummaryBuilder;
use AppBundle\Component\Import\Builder\ImportItemValidationErrorsBuilderInterface;
use AppBundle\Component\Import\DataProvider\TransformerEntityData;
use AppBundle\Entity\Country;
use AppBundle\Entity\State;
use AppBundle\Entity\Tax;
use AppBundle\Util\DateTimeFactory;

abstract class AbstractImportItemToEntityTransformer
{
    protected function transformTax(
        ?string $taxName,
        string $propertyPath,
        TransformerEntityData $transformerEntityData,
        ImportItemValidationErrorsBuilderInterface $validationErrorsBuilder,
        ?ClientErrorSummaryBuilder $errorSummaryBuilder
    ): ?Tax {
        if ($taxName === null) {
            return null;
        }

        $tax = $transformerEntityData->getTax($taxName);
        if (! $tax) {
            $validationErrorsBuilder->addTransformerViolation(
                'Tax %tax% not found.',
                $propertyPath,
                $taxName,
                [
                    '%tax%' => $taxName,
                ]
            );

            if ($errorSummaryBuilder) {
                $errorSummaryBuilder->addMissingTax($taxName);
            }

            return null;
        }

        return $tax;
    }

    protected function transformCountry(
        ?string $countryName,
        string $propertyPath,
        TransformerEntityData $transformerEntityData,
        ImportItemValidationErrorsBuilderInterface $validationErrorsBuilder
    ): ?Country {
        if ($countryName === null) {
            return null;
        }

        $country = $transformerEntityData->getCountry($countryName);
        if (! $country) {
            $validationErrorsBuilder->addTransformerViolation(
                'Country not found.',
                $propertyPath,
                $countryName
            );

            return null;
        }

        return $country;
    }

    protected function transformState(
        ?string $stateName,
        string $propertyPath,
        ?Country $country,
        TransformerEntityData $transformerEntityData,
        ImportItemValidationErrorsBuilderInterface $validationErrorsBuilder
    ): ?State {
        if ($stateName === null) {
            return null;
        }

        $state = $transformerEntityData->getState($stateName);
        if (! $state) {
            $validationErrorsBuilder->addTransformerViolation(
                'State not found.',
                $propertyPath,
                $stateName
            );

            return null;
        }

        if ($country && $state->getCountry() !== $country) {
            $validationErrorsBuilder->addTransformerViolation(
                'State does not belong to this country.',
                $propertyPath,
                $stateName
            );

            return null;
        }

        return $state;
    }

    protected function transformDate(
        ?string $value,
        string $propertyPath,
        ImportItemValidationErrorsBuilderInterface $validationErrorsBuilder
    ): ?\DateTime {
        if ($value === null) {
            return null;
        }

        $date = DateTimeFactory::createFromUnknownFormat($value);
        if (! $date) {
            $validationErrorsBuilder->addTransformerViolation(
                'Date is not in valid format.',
                $propertyPath,
                $value
            );

            return null;
        }

        if ($date < DateTimeFactory::createDate('1900-01-01')) {
            $validationErrorsBuilder->addTransformerViolation(
                'Date must be after 1900-01-01.',
                $propertyPath,
                $value
            );

            return null;
        }

        return $date;
    }

    protected function transformInt(
        ?string $value,
        string $propertyPath,
        string $errorMessage,
        ImportItemValidationErrorsBuilderInterface $validationErrorsBuilder
    ): ?int {
        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            $validationErrorsBuilder->addTransformerViolation(
                $errorMessage,
                $propertyPath,
                $value
            );

            return null;
        }

        return (int) $value;
    }

    protected function transformFloat(
        ?string $value,
        string $propertyPath,
        string $errorMessage,
        ImportItemValidationErrorsBuilderInterface $validationErrorsBuilder
    ): ?float {
        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            $validationErrorsBuilder->addTransformerViolation(
                $errorMessage,
                $propertyPath,
                $value
            );

            return null;
        }

        return (float) $value;
    }

    protected function transformBool(?string $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
