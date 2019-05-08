<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Request;

use AppBundle\Entity\Currency;

class InvoiceCollectionRequest
{
    /**
     * @var int|null
     */
    public $organizationId;

    /**
     * @var int|null
     */
    public $clientId;

    /**
     * @var \DateTimeInterface|null
     */
    public $startDate;

    /**
     * @var \DateTimeInterface|null
     */
    public $endDate;

    /**
     * @var array|null
     */
    public $statuses;

    /**
     * @var string|null
     */
    public $number;

    /**
     * @var int|null
     */
    public $limit;

    /**
     * @var int|null
     */
    public $offset;

    /**
     * @var string|null
     */
    public $order;

    /**
     * @var string|null
     */
    public $direction;

    /**
     * @var bool|null
     */
    public $overdue;

    /**
     * @var Currency|null
     */
    public $currency;

    /**
     * @var string|null
     */
    private $customAttributeKey;

    /**
     * @var string|null
     */
    private $customAttributeValue;

    /**
     * @var bool|null
     */
    public $proforma;

    public function matchByCustomAttribute(string $customAttributeKey, string $customAttributeValue): void
    {
        $this->customAttributeKey = $customAttributeKey;
        $this->customAttributeValue = $customAttributeValue;
    }

    public function getCustomAttributeKey(): ?string
    {
        return $this->customAttributeKey;
    }

    public function getCustomAttributeValue(): ?string
    {
        return $this->customAttributeValue;
    }
}
