<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Entity\CustomAttribute;

class CustomAttributeFactory
{
    public function create(string $type, ?string $attributeType): CustomAttribute
    {
        $attribute = new CustomAttribute();
        $attribute->setType($type);
        $attribute->setAttributeType($attributeType);

        return $attribute;
    }
}
