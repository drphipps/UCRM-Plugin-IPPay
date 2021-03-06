<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Factory;

use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Form\ClientAttributesType;

class ClientAttributesTypeFactory
{
    /**
     * @var CustomAttributeDataProvider
     */
    private $customAttributeDataProvider;

    public function __construct(CustomAttributeDataProvider $customAttributeDataProvider)
    {
        $this->customAttributeDataProvider = $customAttributeDataProvider;
    }

    public function create(): ClientAttributesType
    {
        return new ClientAttributesType(
            $this->customAttributeDataProvider->getByAttributeType(CustomAttribute::ATTRIBUTE_TYPE_CLIENT)
        );
    }
}
