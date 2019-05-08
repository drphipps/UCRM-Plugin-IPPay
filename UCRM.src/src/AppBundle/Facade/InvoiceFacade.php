<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Invoice\CreditApplier;
use AppBundle\Entity\Credit;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemFee;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Option;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Service;
use AppBundle\Event\Invoice\InvoiceAddEvent;
use AppBundle\Event\Invoice\InvoiceDeleteEvent;
use AppBundle\Event\Invoice\InvoiceDraftApprovedEvent;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use AppBundle\Facade\Exception\CannotDeleteProcessedProformaException;
use AppBundle\Facade\Exception\CannotVoidProcessedProformaException;
use AppBundle\Factory\Financial\PaymentTokenFactory;
use AppBundle\Handler\Invoice\PdfHandler;
use AppBundle\RabbitMq\Invoice\ApproveDraftMessage;
use AppBundle\RabbitMq\Invoice\DeleteInvoiceMessage;
use AppBundle\RabbitMq\Invoice\SendInvoiceMessage;
use AppBundle\RabbitMq\Invoice\VoidInvoiceMessage;
use AppBundle\Service\Financial\FinancialTotalCalculator;
use AppBundle\Service\Invoice\InvoiceApprover;
use AppBundle\Service\InvoiceCalculations;
use AppBundle\Service\Options;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionDispatcher;

class InvoiceFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var CreditApplier
     */
    private $creditApplier;

    /**
     * @var FinancialTotalCalculator
     */
    private $financialTotalCalculator;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    /**
     * @var PaymentTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * @var InvoiceCalculations
     */
    private $invoiceCalculations;

    /**
     * @var InvoiceApprover
     */
    private $invoiceApprover;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        CreditApplier $creditApplier,
        FinancialTotalCalculator $financialTotalCalculator,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        TransactionDispatcher $transactionDispatcher,
        PdfHandler $pdfHandler,
        PaymentTokenFactory $paymentTokenFactory,
        InvoiceCalculations $invoiceCalculations,
        InvoiceApprover $invoiceApprover
    ) {
        $this->entityManager = $entityManager;
        $this->options = $options;
        $this->creditApplier = $creditApplier;
        $this->financialTotalCalculator = $financialTotalCalculator;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->pdfHandler = $pdfHandler;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->invoiceCalculations = $invoiceCalculations;
        $this->invoiceApprover = $invoiceApprover;
    }

    /**
     * @todo For now this method has limited functionality and should be used in API only
     *
     * @throws \Exception
     */
    public function handleInvoiceCreate(Invoice $invoice, bool $applyCredit): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($invoice, $applyCredit) {
                $dueDate = clone $invoice->getCreatedDate();
                $dueDate->modify(
                    sprintf(
                        '+%d days',
                        $invoice->getInvoiceMaturityDays() ?? $invoice->getOrganization()->getInvoiceMaturityDays()
                    )
                );
                $invoice->setDueDate($dueDate);

                if ($invoice->getDiscountValue() > 0) {
                    $invoice->setDiscountType(FinancialInterface::DISCOUNT_PERCENTAGE);
                } else {
                    $invoice->setDiscountType(FinancialInterface::DISCOUNT_NONE);
                }

                $this->financialTotalCalculator->computeTotal($invoice);

                if ($invoice->getInvoiceStatus() === Invoice::DRAFT) {
                    $invoice->setInvoiceStatus(Invoice::UNPAID);
                }

                if ($applyCredit) {
                    $this->creditApplier->apply($invoice);
                }

                if (
                    round($invoice->getAmountToPay(), $invoice->getCurrency()->getFractionDigits()) === 0.0
                    && in_array($invoice->getInvoiceStatus(), Invoice::UNPAID_STATUSES, true)
                ) {
                    $invoice->setInvoiceStatus(Invoice::PAID);
                    $invoice->setUncollectible(false);
                }

                // payment token must be created before PDF is generated
                $token = $this->paymentTokenFactory->create($invoice);
                $this->entityManager->persist($token);

                $this->pdfHandler->saveInvoicePdf($invoice);

                $this->entityManager->persist($invoice);

                // update client collection manually to correctly handle subscribers
                $invoice->getClient()->addInvoice($invoice);
                yield new InvoiceAddEvent($invoice);
            }
        );
    }

    /**
     * @todo For now this method has limited functionality and should be used in API only
     *
     * @throws \Exception
     */
    public function handleInvoiceUpdate(Invoice $invoice): void
    {
        $invoiceBeforeUpdate = clone $invoice;

        $this->transactionDispatcher->transactional(
            function () use ($invoice, $invoiceBeforeUpdate) {
                $dueDate = clone $invoice->getCreatedDate();
                $dueDate->modify(
                    sprintf(
                        '+%d days',
                        $invoice->getInvoiceMaturityDays() ?? $invoice->getOrganization()->getInvoiceMaturityDays()
                    )
                );
                $invoice->setDueDate($dueDate);

                $this->financialTotalCalculator->computeTotal($invoice);

                if ($invoice->getInvoiceStatus() === Invoice::DRAFT) {
                    $invoice->setInvoiceStatus(Invoice::UNPAID);
                }

                if (
                    round($invoice->getAmountToPay(), $invoice->getCurrency()->getFractionDigits()) === 0.0
                    && in_array($invoice->getInvoiceStatus(), Invoice::UNPAID_STATUSES, true)
                ) {
                    $invoice->setInvoiceStatus(Invoice::PAID);
                    $invoice->setUncollectible(false);
                }

                $this->pdfHandler->saveInvoicePdf($invoice);

                yield new InvoiceEditEvent($invoice, $invoiceBeforeUpdate);
            }
        );
    }

    public function handleApprove(Invoice $invoice): bool
    {
        if ($invoice->getInvoiceStatus() !== Invoice::DRAFT) {
            return false;
        }

        $this->transactionDispatcher->transactional(
            function () use ($invoice) {
                $invoiceBeforeUpdate = clone $invoice;

                $this->invoiceApprover->approve($invoice);

                yield new InvoiceDraftApprovedEvent($invoice, $invoiceBeforeUpdate);
                yield new InvoiceEditEvent($invoice, $invoiceBeforeUpdate);
            }
        );

        if ($this->isPossibleToSendInvoiceAutomatically($invoice)) {
            $this->rabbitMqEnqueuer->enqueue(new SendInvoiceMessage($invoice));
        }

        return true;
    }

    public function isPossibleToSendInvoiceAutomatically(Invoice $invoice): bool
    {
        if (Helpers::isDemo() || $invoice->getInvoiceStatus() === Invoice::DRAFT) {
            return false;
        }

        $sendEmailCount = 0;
        $serviceCount = 0;
        foreach ($invoice->getInvoiceItems() as $item) {
            if (! $item instanceof InvoiceItemService) {
                continue;
            }

            if (
                $item->getService()
                && (
                    $item->getService()->isSendEmailsAutomatically()
                    ?? $this->options->get(Option::SEND_INVOICE_BY_EMAIL)
                )
            ) {
                ++$sendEmailCount;
            }

            ++$serviceCount;
        }

        $canSend =
            $this->options->get(Option::NOTIFICATION_INVOICE_NEW)
            && (
                $this->options->get(Option::SEND_INVOICE_WITH_ZERO_BALANCE)
                || round($invoice->getAmountToPay(), $invoice->getCurrency()->getFractionDigits()) > 0
            );

        return $canSend
            && $sendEmailCount > 0
            && $sendEmailCount === $serviceCount;
    }

    /**
     * @return array [$added, $failed]
     */
    public function handleAddToApproveQueueMultiple(array $ids): array
    {
        $invoices = $this->entityManager->getRepository(Invoice::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        $added = 0;
        foreach ($invoices as $invoice) {
            if ($invoice->getInvoiceStatus() !== Invoice::DRAFT) {
                continue;
            }

            $this->rabbitMqEnqueuer->enqueue(new ApproveDraftMessage($invoice));
            ++$added;
        }

        return [$added, count($ids) - $added];
    }

    public function handleUseCredit(Invoice $invoice): void
    {
        $invoiceBeforeUpdate = clone $invoice;

        $this->transactionDispatcher->transactional(
            function () use ($invoice, $invoiceBeforeUpdate) {
                if ($this->creditApplier->apply($invoice)) {
                    yield new InvoiceEditEvent($invoice, $invoiceBeforeUpdate);
                }
            }
        );
    }

    public function handleCollectible(Invoice $invoice): void
    {
        $invoiceBeforeUpdate = clone $invoice;

        $invoice->setUncollectible(false);
        $this->transactionDispatcher->transactional(
            function () use ($invoice, $invoiceBeforeUpdate) {
                yield new InvoiceEditEvent($invoice, $invoiceBeforeUpdate);
            }
        );
    }

    public function handleUncollectible(Invoice $invoice): void
    {
        $invoiceBeforeUpdate = clone $invoice;

        $invoice->setUncollectible(true);
        $this->transactionDispatcher->transactional(
            function () use ($invoice, $invoiceBeforeUpdate) {
                yield new InvoiceEditEvent($invoice, $invoiceBeforeUpdate);
            }
        );
    }

    /**
     * @throws CannotDeleteProcessedProformaException
     */
    public function handleDelete(Invoice $invoice): void
    {
        $invoiceId = $invoice->getId();

        $this->setDeleted($invoice);

        $this->transactionDispatcher->transactional(
            function () use ($invoice, $invoiceId) {
                yield new InvoiceDeleteEvent($invoice, $invoiceId);
            }
        );
    }

    /**
     * @throws CannotDeleteProcessedProformaException
     */
    public function handleDeleteMultipleIds(array $ids): void
    {
        $invoices = $this->entityManager->getRepository(Invoice::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        if (count($invoices) === 1) {
            $this->handleDeleteMultiple($invoices);

            return;
        }

        foreach ($invoices as $invoice) {
            $this->rabbitMqEnqueuer->enqueue(new DeleteInvoiceMessage($invoice->getId()));
        }
    }

    /**
     * Used when deleting service and choosing not to keep related invoices.
     *
     * @return array [$deleted, $failed]
     */
    public function handleDeleteMultipleByService(Service $service): array
    {
        $invoices = $this->entityManager->getRepository(Invoice::class)
            ->getServiceInvoices($service);

        $cantDelete = 0;
        foreach ($invoices as $key => $invoice) {
            foreach ($invoice->getInvoiceItems() as $item) {
                if ($item instanceof InvoiceItemService && $item->getService() === $service) {
                    continue;
                }

                if ($item instanceof InvoiceItemFee && $item->getFee() && $item->getFee()->getService() === $service) {
                    continue;
                }

                // Skip invoices with items related to other services.
                ++$cantDelete;
                unset($invoices[$key]);
                break;
            }
        }
        [$deleted, $failed] = $this->handleDeleteMultiple($invoices, false);

        return [$deleted, $failed + $cantDelete];
    }

    /**
     * @param array|Invoice[] $invoices
     *
     * @return array [$deleted, $failed]
     *
     * @throws \Exception
     */
    public function handleDeleteMultiple(array $invoices, bool $restoreFees = true): array
    {
        $count = count($invoices);
        $deleted = 0;

        $this->transactionDispatcher->transactional(
            function () use ($invoices, &$deleted, $restoreFees) {
                foreach ($invoices as $invoice) {
                    $invoiceId = $invoice->getId();

                    $this->setDeleted($invoice, $restoreFees);

                    yield new InvoiceDeleteEvent($invoice, $invoiceId);

                    ++$deleted;
                }
            }
        );

        return [$deleted, $count - $deleted];
    }

    /**
     * @throws CannotVoidProcessedProformaException
     */
    public function handleVoid(Invoice $invoice): bool
    {
        $invoiceBeforeUpdate = clone $invoice;
        if (! $this->setVoid($invoice)) {
            return false;
        }

        $this->transactionDispatcher->transactional(
            function () use ($invoice, $invoiceBeforeUpdate) {
                yield new InvoiceEditEvent($invoice, $invoiceBeforeUpdate);
            }
        );

        return true;
    }

    public function handleVoidMultiple(array $ids): array
    {
        $invoices = $this->entityManager->getRepository(Invoice::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        $voided = 0;
        foreach ($invoices as $invoice) {
            if ($invoice->getInvoiceStatus() === Invoice::VOID) {
                continue;
            }

            $this->rabbitMqEnqueuer->enqueue(new VoidInvoiceMessage($invoice));
            ++$voided;
        }

        return [$voided, count($ids) - $voided];
    }

    public function sendInvoiceEmails(array $invoiceIds): void
    {
        $invoices = $this->entityManager->getRepository(Invoice::class)
            ->findBy(
                [
                    'id' => $invoiceIds,
                ]
            );

        foreach ($invoices as $invoice) {
            $this->rabbitMqEnqueuer->enqueue(new SendInvoiceMessage($invoice));
        }
    }

    public function markAsSendInvoiceEmails(array $invoiceIds): void
    {
        $invoices = $this->entityManager->getRepository(Invoice::class)->findBy(
            [
                'id' => $invoiceIds,
            ]
        );

        $this->transactionDispatcher->transactional(
            function () use ($invoices) {
                foreach ($invoices as $invoice) {
                    $invoiceBeforeUpdate = clone $invoice;
                    $invoice->setEmailSentDate(new \DateTime());

                    yield new InvoiceEditEvent($invoice, $invoiceBeforeUpdate);
                }
            }
        );
    }

    /**
     * @param Invoice[] $invoices
     */
    public function restoreCanCauseSuspension(array $invoices): void
    {
        $this->entityManager->transactional(
            function () use ($invoices) {
                foreach ($invoices as $invoice) {
                    $invoice->setCanCauseSuspension(true);
                }
            }
        );
    }

    private function setVoid(Invoice $invoice): bool
    {
        if ($invoice->getGeneratedInvoice()) {
            throw new CannotVoidProcessedProformaException(
                sprintf(
                    'Proforma invoice %s has processed invoice %s',
                    $invoice->getId(),
                    $invoice->getGeneratedInvoice()->getId()
                )
            );
        }

        if ($invoice->getInvoiceStatus() === Invoice::VOID) {
            return false;
        }
        $this->removePayments($invoice);
        $this->restoreFees($invoice);
        $this->deleteLateFees($invoice);
        $this->unlinkProformaInvoice($invoice);
        $invoice->setInvoiceStatus(Invoice::VOID);

        return true;
    }

    private function setDeleted(Invoice $invoice, bool $restoreFees = true): void
    {
        if ($invoice->getGeneratedInvoice()) {
            throw new CannotDeleteProcessedProformaException(
                sprintf(
                    'Proforma invoice %s has processed invoice %s',
                    $invoice->getId(),
                    $invoice->getGeneratedInvoice()->getId()
                )
            );
        }

        $this->removePayments($invoice);
        if ($restoreFees) {
            $this->restoreFees($invoice);
        }
        $this->deleteLateFees($invoice);
        // update client collection manually to correctly handle subscribers
        $invoice->getClient()->removeInvoice($invoice);

        // Unset relation between proforma and regular invoice.
        if ($proformaInvoice = $invoice->getProformaInvoice()) {
            $this->invoiceCalculations->recalculatePayments($proformaInvoice);
            $proformaInvoice->setGeneratedInvoice(null);
            $proformaInvoice->setInvoiceStatus(Invoice::UNPAID);
        }
        if ($invoice->getGeneratedInvoice()) {
            $invoice->getGeneratedInvoice()->setProformaInvoice(null);
        }

        $this->entityManager->remove($invoice);
    }

    private function unlinkProformaInvoice(Invoice $invoice): void
    {
        if ($proformaInvoice = $invoice->getProformaInvoice()) {
            $proformaInvoice->setGeneratedInvoice(null);
            $proformaInvoice->setAmountPaid(0);
            $proformaInvoice->setInvoiceStatus(Invoice::UNPAID);
            $invoice->setProformaInvoice(null);
        }
    }

    private function removePayments(Invoice $invoice): void
    {
        foreach ($invoice->getPaymentCovers() as $cover) {
            $credit = $cover->getPayment()->getCredit();
            if ($credit) {
                $creditAmount = $credit->getAmount() + $cover->getAmount();
                $credit->setAmount($creditAmount);
                $this->entityManager->persist($credit);
            } else {
                $this->addCredit($cover->getAmount(), $cover->getPayment(), Credit::OVERPAYMENT);
            }
            $invoice->removePaymentCover($cover);
            $this->entityManager->remove($cover);
        }

        $invoice->setAmountPaid(0.0);
    }

    private function addCredit(float $amount, Payment $payment, int $type): void
    {
        if (! $payment->getClient()) {
            return;
        }

        $credit = new Credit();
        $credit->setAmount($amount);
        $credit->setPayment($payment);
        $credit->setClient($payment->getClient());
        $credit->setType($type);
        $payment->setCredit($credit);

        $this->entityManager->persist($credit);
    }

    private function restoreFees(Invoice $invoice): void
    {
        foreach ($invoice->getInvoiceItems() as $item) {
            if (! $item instanceof InvoiceItemFee || ! $item->getFee()) {
                continue;
            }

            $fee = $item->getFee();
            if ($fee->getDueInvoice() && $invoice->getId() === $fee->getDueInvoice()->getId()) {
                $fee->setDueInvoice(null);
            }
            $fee->setInvoiced(false);
        }
    }

    private function deleteLateFees(Invoice $invoice): void
    {
        $invoiceFees = $this->entityManager->getRepository(Fee::class)->findBy(
            [
                'dueInvoice' => $invoice->getId(),
                'type' => Fee::TYPE_LATE_FEE,
            ]
        );

        foreach ($invoiceFees as $invoiceFee) {
            $feeInvoiced = $this->entityManager->getRepository(InvoiceItemFee::class)->findOneBy(
                [
                    'fee' => $invoiceFee->getId(),
                ]
            );

            if ($feeInvoiced) {
                $invoiceFee->setDueInvoice(null);
            } else {
                $this->entityManager->remove($invoiceFee);
            }
        }
    }
}
