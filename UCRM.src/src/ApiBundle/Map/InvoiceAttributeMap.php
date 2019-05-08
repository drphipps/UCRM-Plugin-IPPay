<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class InvoiceAttributeMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $invoiceId;

    /**
     * @Type("integer")
     */
    public $customAttributeId;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $name;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $key;

    /**
     * @Type("string")
     */
    public $value;
}
