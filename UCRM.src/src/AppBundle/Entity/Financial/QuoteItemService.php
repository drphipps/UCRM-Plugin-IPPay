<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Service;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\QuoteItemServiceRepository")
 */
class QuoteItemService extends QuoteItem implements FinancialItemServiceInterface
{
    use FinancialItemServiceTrait;

    /**
     * @var Service|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Service", inversedBy="quoteItemsService")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="service_id")
     */
    protected $service;
}
