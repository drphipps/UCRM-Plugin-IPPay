<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Invoice;

use AppBundle\Entity\Client;
use AppBundle\Factory\Financial\FinancialFactory;
use AppBundle\Factory\Financial\FinancialItemFeeFactory;
use AppBundle\Factory\Financial\FinancialItemServiceFactory;
use AppBundle\Factory\Financial\FinancialItemSurchargeFactory;
use AppBundle\Service\Financial\FinancialTotalCalculator;
use AppBundle\Service\Options;

class InvoiceBuilderFactory
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var FinancialFactory
     */
    private $financialFactory;

    /**
     * @var FinancialItemServiceFactory
     */
    private $financialItemServiceFactory;

    /**
     * @var FinancialItemSurchargeFactory
     */
    private $financialItemSurchargeFactory;

    /**
     * @var FinancialItemFeeFactory
     */
    private $financialItemFeeFactory;

    /**
     * @var FinancialTotalCalculator
     */
    private $financialTotalCalculator;

    public function __construct(
        Options $options,
        FinancialFactory $financialFactory,
        FinancialItemServiceFactory $financialItemServiceFactory,
        FinancialItemSurchargeFactory $financialItemSurchargeFactory,
        FinancialItemFeeFactory $financialItemFeeFactory,
        FinancialTotalCalculator $financialTotalCalculator
    ) {
        $this->options = $options;
        $this->financialFactory = $financialFactory;
        $this->financialItemServiceFactory = $financialItemServiceFactory;
        $this->financialItemSurchargeFactory = $financialItemSurchargeFactory;
        $this->financialItemFeeFactory = $financialItemFeeFactory;
        $this->financialTotalCalculator = $financialTotalCalculator;
    }

    public function create(Client $client): InvoiceBuilder
    {
        return new InvoiceBuilder(
            $this->options,
            $this->financialFactory,
            $this->financialItemServiceFactory,
            $this->financialItemSurchargeFactory,
            $this->financialItemFeeFactory,
            $this->financialTotalCalculator,
            $client
        );
    }
}
