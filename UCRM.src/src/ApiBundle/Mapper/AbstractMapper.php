<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Component\Validator\ValidationErrorCollector;
use ApiBundle\Map\AbstractMap;
use AppBundle\Security\PermissionGrantedChecker;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

abstract class AbstractMapper
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var PropertyAccessorInterface
     */
    protected $accessor;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var ValidationErrorCollector
     */
    protected $errorCollector;

    /**
     * @var PermissionGrantedChecker
     */
    protected $permissionGrantedChecker;

    public function __construct(
        EntityManagerInterface $entityManager,
        Reader $reader,
        ValidationErrorCollector $errorCollector,
        PermissionGrantedChecker $permissionGrantedChecker
    ) {
        $this->entityManager = $entityManager;
        $this->reader = $reader;
        $this->errorCollector = $errorCollector;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
        $this->accessor = (new PropertyAccessorBuilder())->disableMagicCall()->getPropertyAccessor();
    }

    /**
     * Maps Entity map into corresponding Entity.
     *
     * @param object|null $entity
     * @param array       ...$extraEntities
     */
    public function map(AbstractMap $map, $entity, ...$extraEntities)
    {
        $this->validateMap($map);
        if ($entity === null) {
            $entityClassName = $this->getEntityClassName();
            $entity = new $entityClassName();
        } else {
            $this->validateEntity($entity);
        }

        $this->doMap($map, $entity, ...$extraEntities);

        $this->errorCollector->throwErrors();

        return $entity;
    }

    /**
     * Creates Entity map reflecting corresponding Entity.
     *
     * @param object $entity
     */
    public function reflect($entity, array $options = [], ?string $mapClassName = null)
    {
        $this->validateEntity($entity);
        $mapClassName = $mapClassName ?? $this->getMapClassName();
        $map = new $mapClassName();

        $this->doReflect($entity, $map, $options);

        return $map;
    }

    public function reflectCollection(
        iterable $entityCollection,
        array $options = [],
        ?string $mapClassName = null
    ): array {
        $mapCollection = [];
        foreach ($entityCollection as $entity) {
            $mapCollection[] = $this->reflect($entity, $options, $mapClassName);
        }

        return $mapCollection;
    }

    /**
     * This method should return an associative array representing fields,
     * that present both in Entity and Entity map, but have different names.
     * Keys should contain corresponding field names of Entity.
     * Values should contain corresponding field names of Entity map.
     * This representation is mainly used for reflecting validation errors back into Entity map.
     */
    public function getFieldsDifference(): array
    {
        return [];
    }

    /**
     * Checks if Entity map is an instance of corresponding class.
     *
     *
     * @throws \InvalidArgumentException
     */
    protected function validateMap(AbstractMap $map): void
    {
        $mapClassName = $this->getMapClassName();
        if (! $map instanceof $mapClassName) {
            throw new \InvalidArgumentException(
                sprintf('Map value must be instance of %s.', $mapClassName)
            );
        }
    }

    /**
     * Checks if Entity is an instance of corresponding class.
     *
     * @param object $entity
     *
     * @throws \InvalidArgumentException
     */
    protected function validateEntity($entity): void
    {
        $entityClassName = $this->getEntityClassName();
        if (! $entity instanceof $entityClassName) {
            throw new \InvalidArgumentException(
                sprintf('Entity value must be instance of %s.', $entityClassName)
            );
        }
    }

    /**
     * Maps Entity map value into corresponding Entity field.
     *
     * @param object $entity
     */
    protected function mapField(
        $entity,
        AbstractMap $map,
        string $entityFieldName,
        string $mapFieldName = null,
        string $associatedEntityClassName = null,
        array $associatedEntityExtraConditions = []
    ): void {
        if ($mapFieldName === null) {
            $mapFieldName = $entityFieldName;
        }

        if (! $map->isFieldInInput($mapFieldName)) {
            return;
        }

        try {
            $value = $this->accessor->getValue($map, $mapFieldName);

            if ($value === '') {
                $value = null;
            }

            if ($value === null) {
                if (! property_exists($entity, $entityFieldName)) {
                    throw new \InvalidArgumentException();
                }

                $annotations = $this->reader->getPropertyAnnotations(
                    new \ReflectionProperty($entity, $entityFieldName)
                );
                foreach ($annotations as $annotation) {
                    if (($annotation instanceof Column || $annotation instanceof JoinColumn)
                        && ! $annotation->nullable
                    ) {
                        throw new \InvalidArgumentException();
                    }
                }
            } elseif ($associatedEntityClassName !== null) {
                $value = $this->getAssociatedEntity(
                    $associatedEntityClassName,
                    $value,
                    $associatedEntityExtraConditions
                );
            }

            $this->accessor->setValue($entity, $entityFieldName, $value);
        } catch (\InvalidArgumentException $e) {
            $this->errorCollector->add(
                $this->getFieldsDifference()[$entityFieldName] ?? $entityFieldName,
                'This value is not valid.'
            );
        }
    }

    /**
     * Reflects Entity value into corresponding Entity map field.
     */
    protected function reflectField(
        AbstractMap $map,
        string $fieldName,
        $value,
        string $associatedEntityFieldName = null
    ): void {
        if (is_object($value) && $associatedEntityFieldName !== null) {
            $value = $this->accessor->getValue($value, $associatedEntityFieldName);
        }

        if ($value !== null) {
            $this->accessor->setValue($map, $fieldName, $value);
        }
    }

    /**
     * Tries to find associated Entity by its Primary Key and returns it if found.
     *
     *
     *
     * @throws NotFoundHttpException
     */
    protected function getAssociatedEntity(
        string $associatedEntityClassName,
        int $associatedEntityId,
        array $associatedEntityExtraConditions = []
    ) {
        $associatedEntity = $this->entityManager->getRepository($associatedEntityClassName)->findOneBy(
            array_merge(
                [
                    'id' => $associatedEntityId,
                ],
                $associatedEntityExtraConditions
            )
        );
        if (! $associatedEntity
            || ($this->accessor->isReadable($associatedEntity, 'deletedAt')
                && $associatedEntity->isDeleted())
        ) {
            throw new NotFoundHttpException(
                sprintf('%s with id %s not found.', $associatedEntityClassName, $associatedEntityId)
            );
        }

        return $associatedEntity;
    }

    abstract protected function getMapClassName(): string;

    abstract protected function getEntityClassName(): string;

    /**
     * Implements mapping logic unique for each Entity.
     *
     * @param object $entity
     */
    abstract protected function doMap(AbstractMap $map, $entity): void;

    /**
     * Implements reflecting logic unique for each Entity.
     *
     * @param object $entity
     */
    abstract protected function doReflect($entity, AbstractMap $map, array $options = []);
}
