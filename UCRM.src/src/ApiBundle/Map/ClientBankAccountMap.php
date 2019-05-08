<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

class ClientBankAccountMap extends AbstractMap
{
    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $accountNumber;
}
