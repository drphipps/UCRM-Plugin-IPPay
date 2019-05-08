<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial;

use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\FinancialItemFeeInterface;
use AppBundle\Entity\Financial\InvoiceItemFee;
use AppBundle\Entity\Financial\QuoteItemFee;
use AppBundle\Entity\Option;
use AppBundle\Entity\Tax;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;

class FinancialItemFeeFactory
{
    private const TAX_OPTIONS = [
        Fee::TYPE_LATE_FEE => Option::LATE_FEE_TAX_ID,
        Fee::TYPE_SETUP_FEE => Option::SETUP_FEE_TAX_ID,
        Fee::TYPE_EARLY_TERMINATION_FEE => Option::EARLY_TERMINATION_FEE_TAX_ID,
    ];

    /**
     * @var Options
     */
    private $options;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(Options $options, EntityManagerInterface $entityManager)
    {
        $this->options = $options;
        $this->entityManager = $entityManager;
    }

    public function createInvoiceItem(Fee $fee): InvoiceItemFee
    {
        $item = new InvoiceItemFee();
        $this->setData($item, $fee);

        return $item;
    }

    public function createQuoteItem(Fee $fee): QuoteItemFee
    {
        $item = new QuoteItemFee();
        $this->setData($item, $fee);

        return $item;
    }

    private function setData(FinancialItemFeeInterface $item, Fee $fee)
    {
        $item->setFee($fee);
        $item->setTaxable($fee->isTaxable());
        $item->setPrice($fee->getPrice());
        $item->setLabel($fee->getInvoiceLabel() ?? $fee->getName());
        $item->setQuantity(1.0);
        $item->setTotal($item->getPrice() * $item->getQuantity());

        if ($fee->isTaxable()) {
            $client = $fee->getClient();

            $tax = $this->getTaxFromOptions($fee);

            if ($tax) {
                $item->setTax1($tax);
            } else {
                $item->setTax1($client->getTax1());
                $item->setTax2($client->getTax2());
                $item->setTax3($client->getTax3());
            }
        }
    }

    private function getTaxFromOptions(Fee $fee): ?Tax
    {
        if (! array_key_exists($fee->getType(), self::TAX_OPTIONS)) {
            throw new \InvalidArgumentException(sprintf('Unknown fee type "%s".', $fee->getType()));
        }

        $taxId = $this->options->get(self::TAX_OPTIONS[$fee->getType()]);

        return $taxId
            ? $this->entityManager->getRepository(Tax::class)->find($taxId)
            : null;
    }
}
