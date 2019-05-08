<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ServiceUsageMap;
use AppBundle\Component\Service\PeriodDataUsageData;

class ServiceUsageMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ServiceUsageMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return PeriodDataUsageData::class;
    }

    /**
     * @param ServiceUsageMap     $map
     * @param PeriodDataUsageData $dataObject
     */
    protected function doMap(AbstractMap $map, $dataObject): void
    {
        $this->mapField($dataObject, $map, 'download');
        $this->mapField($dataObject, $map, 'upload');
        $this->mapField($dataObject, $map, 'downloadLimit');
        $this->mapField($dataObject, $map, 'unit');
        $this->mapField($dataObject, $map, 'startDate');
        $this->mapField($dataObject, $map, 'endDate');
    }

    /**
     * @param PeriodDataUsageData $dataObject
     * @param ServiceUsageMap     $map
     */
    protected function doReflect($dataObject, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'download', $dataObject->download);
        $this->reflectField($map, 'upload', $dataObject->upload);
        $this->reflectField($map, 'downloadLimit', $dataObject->downloadLimit);
        $this->reflectField($map, 'unit', $dataObject->unit);
        $this->reflectField($map, 'startDate', $dataObject->startDate);
        $this->reflectField($map, 'endDate', $dataObject->endDate);
    }
}
