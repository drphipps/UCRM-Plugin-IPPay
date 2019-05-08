<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Builder;

use AppBundle\Component\Import\Transformer\ConstraintViolationTransformer;
use AppBundle\Entity\Import\ClientErrorSummary;
use AppBundle\Entity\Import\ClientErrorSummaryItem;
use AppBundle\Entity\Import\ImportItemInterface;
use AppBundle\Entity\Import\ImportItemValidationErrorsInterface;
use Doctrine\Common\Collections\ArrayCollection;

class ClientErrorSummaryBuilder
{
    /**
     * @var ConstraintViolationTransformer
     */
    private $constraintViolationTransformer;

    /**
     * @var ClientErrorSummary
     */
    private $errorSummary;

    /**
     * @var int
     */
    private $erroneousClientCount = 0;

    /**
     * @var string[]
     */
    private $missingTaxes = [];

    /**
     * @var string[]
     */
    private $missingServicePlans = [];

    /**
     * @var ClientErrorSummaryItem[] - indexed by hash
     */
    private $items = [];

    public function __construct(ConstraintViolationTransformer $constraintViolationTransformer)
    {
        $this->constraintViolationTransformer = $constraintViolationTransformer;

        $this->errorSummary = new ClientErrorSummary();
    }

    public function addValidationErrors(?ImportItemValidationErrorsInterface $validationErrors): void
    {
        if (! $validationErrors) {
            return;
        }

        foreach ($validationErrors->getErrors() as $error) {
            $this->addError(
                $error,
                $validationErrors->getImportItem()
            );
        }
    }

    public function addMissingTax(string $taxName): void
    {
        if (in_array($taxName, $this->missingTaxes, true)) {
            return;
        }

        $this->missingTaxes[] = $taxName;
    }

    public function addMissingServicePlan(string $servicePlanName): void
    {
        if (in_array($servicePlanName, $this->missingServicePlans, true)) {
            return;
        }

        $this->missingServicePlans[] = $servicePlanName;
    }

    public function increaseErroneousClientCounter(): void
    {
        ++$this->erroneousClientCount;
    }

    public function getClientErrorSummary(): ClientErrorSummary
    {
        $this->errorSummary->setErrorSummaryItems(new ArrayCollection($this->items));
        $this->errorSummary->setErroneousClientCount($this->erroneousClientCount);
        $this->errorSummary->setMissingTaxes($this->missingTaxes);
        $this->errorSummary->setMissingServicePlans($this->missingServicePlans);

        return $this->errorSummary;
    }

    /**
     * @param mixed[] $error
     */
    private function addError(array $error, ImportItemInterface $importItem): void
    {
        if (
            ! $this->constraintViolationTransformer->isValidArray($error)
            || $this->isIgnored($error['isTransformerViolation'], $error['propertyPath'])
        ) {
            return;
        }

        $hash = $this->constraintViolationTransformer->toSummaryHash($error);
        if (! array_key_exists($hash, $this->items)) {
            $this->items[$hash] = new ClientErrorSummaryItem();
            $this->items[$hash]->setHash($hash);
            $this->items[$hash]->setError($error);
            $this->items[$hash]->setErrorSummary($this->errorSummary);
            $this->items[$hash]->setType($importItem->getErrorSummaryType());
        }

        $this->items[$hash]->increaseCount();
        $this->items[$hash]->addLineNumber($importItem->getLineNumber());
    }

    /**
     * Missing taxes and service plans are handled outside of error summary.
     */
    private function isIgnored(bool $isTransformerViolation, string $propertyPath): bool
    {
        return $isTransformerViolation
            && in_array(
                $propertyPath,
                [
                    'tax1',
                    'tax2',
                    'tax3',
                    'tariff',
                ],
                true
            );
    }
}
