<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Invoice\CreditApplier;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemFee;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Event\Invoice\InvoiceAddEvent;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use AppBundle\Event\Quote\QuoteAddEvent;
use AppBundle\Event\Quote\QuoteEditEvent;
use AppBundle\Factory\Financial\PaymentTokenFactory;
use AppBundle\Handler\Invoice\PdfHandler as InvoicePdfHandler;
use AppBundle\Handler\Quote\PdfHandler as QuotePdfHandler;
use AppBundle\Service\Financial\FinancialTotalCalculator;
use AppBundle\Service\Financial\InvoiceTaxableSupplyDateCalculator;
use AppBundle\Transformer\Financial\FormCollectionsTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use TransactionEventsBundle\TransactionDispatcher;

class FinancialFormFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var FinancialTotalCalculator
     */
    private $financialTotalCalculator;

    /**
     * @var FormCollectionsTransformer
     */
    private $formCollectionsTransformer;

    /**
     * @var CreditApplier
     */
    private $creditApplier;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var InvoicePdfHandler
     */
    private $invoicePdfHandler;

    /**
     * @var QuotePdfHandler
     */
    private $quotePdfHandler;

    /**
     * @var PaymentTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * @var InvoiceTaxableSupplyDateCalculator
     */
    private $invoiceTaxableSupplyDateCalculator;

    public function __construct(
        EntityManagerInterface $entityManager,
        FinancialTotalCalculator $financialTotalCalculator,
        FormCollectionsTransformer $formCollectionsTransformer,
        CreditApplier $creditApplier,
        TransactionDispatcher $transactionDispatcher,
        InvoicePdfHandler $invoicePdfHandler,
        QuotePdfHandler $quotePdfHandler,
        PaymentTokenFactory $paymentTokenFactory,
        InvoiceTaxableSupplyDateCalculator $invoiceTaxableSupplyDateCalculator
    ) {
        $this->entityManager = $entityManager;
        $this->financialTotalCalculator = $financialTotalCalculator;
        $this->formCollectionsTransformer = $formCollectionsTransformer;
        $this->creditApplier = $creditApplier;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->invoicePdfHandler = $invoicePdfHandler;
        $this->quotePdfHandler = $quotePdfHandler;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->invoiceTaxableSupplyDateCalculator = $invoiceTaxableSupplyDateCalculator;
    }

    /**
     * Handles setting fields not directly in form on the Invoice entity,
     * correct item collection updates and calculating the invoice total.
     *
     * Used both for recalculation signal and when the form is really submitted.
     */
    public function processForm(FormInterface $form, FinancialInterface $financial, Collection $oldItems): void
    {
        $client = $financial->getClient();

        $bankAccount = $form->has('organizationBankAccount')
            ? $form->get('organizationBankAccount')->getData()
            : null;
        if ($bankAccount !== $client->getOrganization()->getBankAccount()) {
            $financial->setOrganizationBankAccountField1($bankAccount ? $bankAccount->getField1() : null);
            $financial->setOrganizationBankAccountField2($bankAccount ? $bankAccount->getField2() : null);
            $financial->setOrganizationBankAccountName($bankAccount ? $bankAccount->getName() : null);
        }

        // Quote does not have a due date.
        if ($financial instanceof Invoice) {
            // recalculate signal can have empty createdDate
            $dueDate = $financial->getCreatedDate() ? clone $financial->getCreatedDate() : new \DateTime();
            $dueDate->modify(
                sprintf(
                    '+%d days',
                    $financial->getInvoiceMaturityDays() ?? $financial->getOrganization()->getInvoiceMaturityDays()
                )
            );
            $financial->setDueDate($dueDate);
        }

        if ($financial->getDiscountValue() > 0) {
            $financial->setDiscountType(FinancialInterface::DISCOUNT_PERCENTAGE);
        } else {
            $financial->setDiscountType(FinancialInterface::DISCOUNT_NONE);
        }

        $items = $this->formCollectionsTransformer->getItemsFromFormData($form, $financial);
        foreach ($oldItems as $oldItem) {
            if (! $items->contains($oldItem)) {
                // InvoiceItemFee is intentional here, don't use FinancialItemFeeInterface,
                // we're setting the "invoiced" boolean.
                if ($oldItem instanceof InvoiceItemFee) {
                    $fee = $oldItem->getFee();
                    $fee->setInvoiced(false);
                }
                $financial->getItems()->removeElement($oldItem);
                $this->entityManager->remove($oldItem);
            }
        }

        foreach ($items as $item) {
            if (! $financial->getItems()->contains($item)) {
                // InvoiceItemFee is intentional here, don't use FinancialItemFeeInterface,
                // we're setting the "invoiced" boolean.
                if ($item instanceof InvoiceItemFee) {
                    $fee = $item->getFee();
                    $fee->setInvoiced(true);
                }
                $financial->getItems()->add($item);
            }
        }

        // Compute after items are updated
        if ($financial instanceof Invoice) {
            $financial->setTaxableSupplyDate(
                $this->invoiceTaxableSupplyDateCalculator->computeTaxableSupplyDate($financial)
            );
        }

        $this->financialTotalCalculator->computeTotal($financial);
    }

    public function handleSubmitInvoice(
        FormInterface $form,
        Invoice $invoice,
        ArrayCollection $oldInvoiceItems,
        bool $applyCredit,
        ?Invoice $invoiceBeforeUpdate
    ): void {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use (
                $invoice,
                $form,
                $oldInvoiceItems,
                $applyCredit,
                $invoiceBeforeUpdate
            ) {
                $this->processForm($form, $invoice, $oldInvoiceItems);

                // When editing draft, it's always automatically approved (i.e. status is changed to UNPAID).
                if ($invoice->getInvoiceStatus() === Invoice::DRAFT) {
                    $invoice->setInvoiceStatus(Invoice::UNPAID);
                }

                // When requested, apply credit and recalculate invoice status.
                if ($applyCredit) {
                    $this->creditApplier->apply($invoice);
                }

                // When amount to pay is zero, the status has to be PAID.
                if (
                    round($invoice->getAmountToPay(), $invoice->getCurrency()->getFractionDigits()) === 0.0
                    && in_array($invoice->getInvoiceStatus(), Invoice::UNPAID_STATUSES, true)
                ) {
                    $invoice->setInvoiceStatus(Invoice::PAID);
                    $invoice->setUncollectible(false);
                }

                if (! $invoice->getPaymentToken()) {
                    // payment token must be created before PDF is generated
                    $token = $this->paymentTokenFactory->create($invoice);
                    $entityManager->persist($token);
                }

                // Generates and saves the invoice PDF.
                $this->invoicePdfHandler->saveInvoicePdf($invoice);

                $entityManager->persist($invoice);
                if ($invoiceBeforeUpdate) {
                    yield new InvoiceEditEvent(
                        $invoice,
                        $invoiceBeforeUpdate
                    );
                } else {
                    // update client collection manually to correctly handle subscribers
                    $invoice->getClient()->addInvoice($invoice);
                    yield new InvoiceAddEvent($invoice);
                }
            },
            Connection::TRANSACTION_REPEATABLE_READ
        );
    }

    public function handleSubmitQuote(
        FormInterface $form,
        Quote $quote,
        ArrayCollection $oldQuoteItems,
        ?Quote $quoteBeforeUpdate
    ): void {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use (
                $quote,
                $form,
                $oldQuoteItems,
                $quoteBeforeUpdate
            ) {
                $this->processForm($form, $quote, $oldQuoteItems);

                // Generates and saves the quote PDF.
                $this->quotePdfHandler->saveQuotePdf($quote);

                $entityManager->persist($quote);
                if ($quoteBeforeUpdate) {
                    yield new QuoteEditEvent(
                        $quote,
                        $quoteBeforeUpdate
                    );
                } else {
                    yield new QuoteAddEvent($quote);
                }
            }
        );
    }
}
