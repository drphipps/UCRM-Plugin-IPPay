<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Invoice;

use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;

class LateFeeCreator
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Options
     */
    private $options;

    public function __construct(EntityManager $em, Options $options)
    {
        $this->em = $em;
        $this->options = $options;
    }

    /**
     * Creates late fees for overdue invoices. If there is already existing fee for invoice, no fee is created.
     */
    public function create(): void
    {
        if (! $this->options->get(Option::LATE_FEE_ACTIVE) || empty($this->options->get(Option::LATE_FEE_PRICE))) {
            return;
        }

        $invoices = $this->em->getRepository(InvoiceItemService::class)
            ->getOverdueInvoicesForLateFees($this->options->get(Option::LATE_FEE_DELAY_DAYS));

        foreach ($invoices as $invoice) {
            $fee = $this->createLateFee($invoice);

            if ($fee->getPrice() > 0) {
                $this->em->persist($fee);
            }

            $invoice->setLateFeeCreated(true);
        }

        $this->em->flush();
    }

    private function createLateFee(Invoice $invoice): Fee
    {
        $fee = new Fee();
        $fee->setType(Fee::TYPE_LATE_FEE);
        $fee->setClient($invoice->getClient());
        $fee->setCreatedDate(new \DateTime());
        $fee->setDueInvoice($invoice);
        $fee->setInvoiceLabel($this->options->get(Option::LATE_FEE_INVOICE_LABEL));
        $fee->setName($this->options->get(Option::LATE_FEE_INVOICE_LABEL));
        $fee->setTaxable($this->options->get(Option::LATE_FEE_TAXABLE));
        $fee->setInvoiced(false);

        $feePrice = $this->options->get(Option::LATE_FEE_PRICE);

        // if type is percentage, fee price is set from total invoice price (even for partially paid invoices)
        if ($this->options->get(Option::LATE_FEE_PRICE_TYPE) === Fee::PRICE_TYPE_PERCENTAGE) {
            $feePrice = $invoice->getTotal() * $feePrice / 100;
        }
        $fee->setPrice($feePrice);

        return $fee;
    }
}
