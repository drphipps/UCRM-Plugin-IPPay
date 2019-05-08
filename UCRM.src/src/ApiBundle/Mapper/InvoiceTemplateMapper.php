<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\FinancialTemplateMap;
use AppBundle\Entity\Financial\InvoiceTemplate;

class InvoiceTemplateMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return FinancialTemplateMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return InvoiceTemplate::class;
    }

    /**
     * @param InvoiceTemplate $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        throw new \RuntimeException('This method is not supported');
    }

    /**
     * @param InvoiceTemplate $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'createdDate', $entity->getCreatedDate());
        $this->reflectField($map, 'isOfficial', null !== $entity->getOfficialName());
        $this->reflectField($map, 'isValid', $entity->getIsValid());
    }
}
