<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Request;

class ClientCollectionRequest
{
    /**
     * @var int|null
     */
    public $organizationId;

    /**
     * @var bool|null
     */
    public $isLead;

    /**
     * @var string|null
     */
    public $userIdent;

    /**
     * @var string|null
     */
    public $order;

    /**
     * @var string|null
     */
    public $direction;

    /**
     * @var string|null
     */
    private $customAttributeKey;

    /**
     * @var string|null
     */
    private $customAttributeValue;

    /**
     * @var int|null
     */
    public $limit;

    /**
     * @var int|null
     */
    public $offset;

    public function matchByCustomAttribute(string $customAttributeKey, string $customAttributeValue): void
    {
        $this->customAttributeKey = $customAttributeKey;
        $this->customAttributeValue = $customAttributeValue;
    }

    public function getCustomAttributeKey()
    {
        return $this->customAttributeKey;
    }

    public function getCustomAttributeValue()
    {
        return $this->customAttributeValue;
    }
}
