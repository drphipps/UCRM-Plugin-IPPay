<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid\Filter;

interface RangeFilterInterface
{
    public function getRangeFrom();

    public function setRangeFrom($rangeFrom);

    public function getRangeTo();

    public function setRangeTo($rangeTo);

    public function refreshControlPrototype();
}
