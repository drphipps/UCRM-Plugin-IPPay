<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\SettingsDataInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManager;

class OptionsManager
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Option[]
     */
    private $optionData;

    public function __construct(EntityManager $em, Reader $reader, Options $options)
    {
        $this->em = $em;
        $this->reader = $reader;
        $this->options = $options;
    }

    public function loadOptionsIntoDataClass(string $dataClassName): SettingsDataInterface
    {
        if (empty($this->optionData)) {
            $this->load();
        }

        $dataClass = new $dataClassName();
        $this->map(
            $dataClass,
            function (\ReflectionProperty $property, SettingsDataInterface $dataClass, Option $option) {
                $property->setValue($dataClass, $option->getTypedValue());
            }
        );

        return $dataClass;
    }

    public function updateOptions(SettingsDataInterface $data): void
    {
        if (empty($this->optionData)) {
            $this->load();
        }

        $this->map(
            $data,
            function (\ReflectionProperty $property, SettingsDataInterface $data, Option $option) {
                $value = $property->getValue($data);
                $option->setValue(null !== $value ? (string) $value : null);
            }
        );

        $this->em->flush();
        $this->options->refresh();
    }

    private function load(): void
    {
        $options = $this->em->getRepository(Option::class)->findAll();
        foreach ($options as $option) {
            $this->optionData[$option->getCode()] = $option;
        }
    }

    private function map(SettingsDataInterface $dataClass, \Closure $callback): void
    {
        $reflectionClass = new \ReflectionClass($dataClass);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $identifier = $this->reader->getPropertyAnnotation($reflectionProperty, Identifier::class);
            if ($identifier) {
                $option = $this->getOption($identifier->id);
                $callback($reflectionProperty, $dataClass, $option);
            }
        }
    }

    private function getOption(string $code): Option
    {
        if (! array_key_exists($code, $this->optionData)) {
            throw new \OutOfBoundsException(sprintf('Option with code %s does not exist.', $code));
        }

        return $this->optionData[$code];
    }
}
