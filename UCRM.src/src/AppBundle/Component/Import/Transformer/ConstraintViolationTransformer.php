<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Transformer;

use AppBundle\Component\Import\Annotation\CsvColumn;
use AppBundle\Component\Import\DataProvider\CsvColumnDataProvider;
use AppBundle\Component\Import\Validator\TransformerConstraintViolation;
use AppBundle\Entity\Import\ClientErrorSummaryItem;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ServiceImportItem;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

class ConstraintViolationTransformer
{
    /**
     * @var CsvColumn[][]
     */
    private $csvColumns = [];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CsvColumnDataProvider
     */
    private $csvColumnDataProvider;

    public function __construct(
        TranslatorInterface $translator,
        CsvColumnDataProvider $csvColumnDataProvider
    ) {
        $this->translator = $translator;
        $this->csvColumnDataProvider = $csvColumnDataProvider;
    }

    public function toArray(ConstraintViolationInterface $item): array
    {
        return [
            'message' => $item->getMessage(),
            'messageTemplate' => $item->getMessageTemplate(),
            'parameters' => $item->getParameters(),
            'plural' => $item->getPlural(),
            'propertyPath' => $item->getPropertyPath(),
            'invalidValue' => $item->getInvalidValue(),
            'isTransformerViolation' => $item instanceof TransformerConstraintViolation,
        ];
    }

    public function toSummaryHash(array $data): string
    {
        if (! $this->isValidArray($data)) {
            throw new \InvalidArgumentException('Invalid data.');
        }

        return sha1(
            json_encode(
                [
                    $data['messageTemplate'],
                    $data['parameters'],
                    $data['propertyPath'],
                ]
            )
        );
    }

    public function toTranslatedString(array $data, string $type): string
    {
        if (! $this->isValidArray($data)) {
            throw new \InvalidArgumentException('Invalid data.');
        }

        $errorMessage = $this->translator->trans($data['messageTemplate'], $data['parameters'], 'validators');

        $fieldLabel = $this->getFieldLabel($data['propertyPath'], $type);
        if ($fieldLabel === null) {
            return $errorMessage;
        }

        return sprintf('%s: %s', $this->translator->trans($fieldLabel), $errorMessage);
    }

    public function isValidArray(array $data): bool
    {
        return array_key_exists('message', $data)
            && array_key_exists('messageTemplate', $data)
            && array_key_exists('parameters', $data)
            && ($data['parameters'] === null || is_array($data['parameters']))
            && array_key_exists('plural', $data)
            && array_key_exists('propertyPath', $data)
            && array_key_exists('invalidValue', $data)
            && array_key_exists('isTransformerViolation', $data);
    }

    private function getFieldLabel(string $propertyPath, string $type): ?string
    {
        foreach ($this->getCsvColumns($type) as $column) {
            if ($column->errorPropertyPath === $propertyPath) {
                return $column->label;
            }
        }

        return null;
    }

    /**
     * @return CsvColumn[]
     */
    private function getCsvColumns(string $type): array
    {
        if (! array_key_exists($type, $this->csvColumns)) {
            $this->csvColumns[$type] = $this->csvColumnDataProvider->getCsvColumns(
                new \ReflectionClass($this->getItemClassFromType($type))
            );
        }

        return $this->csvColumns[$type];
    }

    private function getItemClassFromType(string $type): string
    {
        switch ($type) {
            case ClientErrorSummaryItem::TYPE_CLIENT:
                return ClientImportItem::class;
            case ClientErrorSummaryItem::TYPE_SERVICE:
                return ServiceImportItem::class;
            default:
                throw new \InvalidArgumentException('Not supported.');
        }
    }
}
