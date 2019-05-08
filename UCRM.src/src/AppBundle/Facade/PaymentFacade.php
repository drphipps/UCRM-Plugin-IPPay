<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Client;
use AppBundle\Entity\Download;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentCover;
use AppBundle\Entity\PaymentCustom;
use AppBundle\Entity\PaymentDetailsInterface;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\PaymentProvider;
use AppBundle\Entity\PaymentToken;
use AppBundle\Entity\User;
use AppBundle\Event\Payment\PaymentAddEvent;
use AppBundle\Event\Payment\PaymentDeleteEvent;
use AppBundle\Event\Payment\PaymentEditEvent;
use AppBundle\Event\Payment\PaymentUnmatchEvent;
use AppBundle\Facade\Exception\InvalidPaymentCurrencyException;
use AppBundle\RabbitMq\Payment\ExportPaymentOverviewMessage;
use AppBundle\RabbitMq\Payment\ExportPaymentReceiptMessage;
use AppBundle\Service\ClientStatusUpdater;
use AppBundle\Service\InvoiceCalculations;
use AppBundle\Service\Options;
use AppBundle\Service\Payment\PaymentCoversGenerator;
use AppBundle\Service\ServiceStatusUpdater;
use Doctrine\ORM\EntityManager;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionDispatcher;

class PaymentFacade
{
    /**
     * @var ClientStatusUpdater
     */
    private $clientStatusUpdater;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var InvoiceCalculations
     */
    private $invoiceCalculations;

    /**
     * @var PaymentCoversGenerator
     */
    private $paymentCoversGenerator;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var ServiceStatusUpdater
     */
    private $serviceStatusUpdater;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var Options
     */
    private $options;

    public function __construct(
        ClientStatusUpdater $clientStatusUpdater,
        EntityManager $entityManager,
        InvoiceCalculations $invoiceCalculations,
        PaymentCoversGenerator $paymentCoversGenerator,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        ServiceStatusUpdater $serviceStatusUpdater,
        TransactionDispatcher $transactionDispatcher,
        Options $options
    ) {
        $this->clientStatusUpdater = $clientStatusUpdater;
        $this->entityManager = $entityManager;
        $this->invoiceCalculations = $invoiceCalculations;
        $this->paymentCoversGenerator = $paymentCoversGenerator;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->serviceStatusUpdater = $serviceStatusUpdater;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->options = $options;
    }

    public function setDefaults(Payment $payment, ?PaymentCustom $paymentCustom = null, ?User $user = null): void
    {
        $payment->setCreatedDate(new \DateTime());
        if ($paymentCustom) {
            $paymentCustom->setProviderPaymentTime(new \DateTime());
        }

        $organization = $this->entityManager->getRepository(Organization::class)->getFirstSelected();
        if ($organization) {
            $payment->setCurrency($organization->getCurrency());
        }

        if ($user) {
            $payment->setUser($user);
        }
    }

    /**
     * @throws InvalidPaymentCurrencyException
     */
    public function handleCreate(
        Payment $payment,
        array $invoices,
        ?PaymentDetailsInterface $paymentDetails = null,
        ?bool $sendReceipt = null
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($payment, $invoices, $paymentDetails, $sendReceipt) {
                $payment->setSendReceipt($sendReceipt ?? $this->options->get(Option::SEND_PAYMENT_RECEIPTS));
                if (! $payment->getClient()) {
                    $payment->setSendReceipt(false);
                }

                $this->resolveCurrency($payment, $paymentDetails);
                if ($paymentDetails) {
                    $this->flushPaymentDetails($payment, $paymentDetails);
                }
                $this->entityManager->persist($payment);
                $this->paymentCoversGenerator->processPayment($payment, $invoices);

                yield new PaymentAddEvent($payment);
            }
        );
    }

    /**
     * @throws InvalidPaymentCurrencyException
     */
    public function handleCreateWithInvoiceIds(
        Payment $payment,
        array $invoiceIds,
        ?PaymentCustom $paymentDetails = null,
        ?bool $sendReceipt = null
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($payment, $invoiceIds, $paymentDetails, $sendReceipt) {
                $payment->setSendReceipt($sendReceipt ?? $this->options->get(Option::SEND_PAYMENT_RECEIPTS));

                $this->resolveCurrency($payment, $paymentDetails);
                if ($paymentDetails) {
                    $this->flushPaymentDetails($payment, $paymentDetails);
                }

                $invoices = $invoiceIds
                    ? $this->entityManager->getRepository(Invoice::class)->findBy(['id' => $invoiceIds])
                    : [];

                $this->entityManager->persist($payment);
                $this->paymentCoversGenerator->processPayment($payment, $invoices);

                yield new PaymentAddEvent($payment);
            }
        );
    }

    /**
     * @throws InvalidPaymentCurrencyException
     */
    public function handleCreateWithoutInvoiceIds(
        Payment $payment,
        ?PaymentCustom $paymentDetails = null,
        ?bool $sendReceipt = null
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($payment, $paymentDetails, $sendReceipt) {
                $payment->setSendReceipt($sendReceipt ?? $this->options->get(Option::SEND_PAYMENT_RECEIPTS));

                $this->resolveCurrency($payment, $paymentDetails);
                if ($paymentDetails) {
                    $this->flushPaymentDetails($payment, $paymentDetails);
                }

                $invoices = $this->resolvePaidInvoices($payment, []);
                $this->entityManager->persist($payment);
                $this->paymentCoversGenerator->processPayment($payment, $invoices);

                yield new PaymentAddEvent($payment);
            }
        );
    }

    public function handleCreateMultipleWithoutInvoiceIds(
        array $payments,
        ?bool $sendReceipt = null
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($payments, $sendReceipt) {
                foreach ($payments as $payment) {
                    $payment->setSendReceipt($sendReceipt ?? $this->options->get(Option::SEND_PAYMENT_RECEIPTS));
                    $this->resolveCurrency($payment);
                    $invoices = $this->resolvePaidInvoices($payment, []);
                    $this->entityManager->persist($payment);
                    $this->paymentCoversGenerator->processPayment($payment, $invoices);

                    yield new PaymentAddEvent($payment);
                }
            }
        );
    }

    /**
     * @throws InvalidPaymentCurrencyException
     */
    public function handleCreateOnlinePayment(
        Payment $payment,
        PaymentDetailsInterface $paymentDetails,
        PaymentToken $paymentToken,
        ?bool $sendReceipt = null
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($payment, $paymentDetails, $paymentToken, $sendReceipt) {
                $payment->setSendReceipt($sendReceipt ?? $this->options->get(Option::SEND_PAYMENT_RECEIPTS));
                $this->resolveCurrency($payment, $paymentDetails);
                $this->flushPaymentDetails($payment, $paymentDetails);
                $invoices = $this->resolvePaidInvoices($payment, [$paymentToken->getInvoice()]);
                $paymentToken->getInvoice()->setPaymentToken(null);
                $this->entityManager->remove($paymentToken);
                $this->entityManager->persist($payment);
                $this->paymentCoversGenerator->processPayment($payment, $invoices);

                yield new PaymentAddEvent($payment);
            }
        );
    }

    public function handleCreateOnlinePaymentUsingSubscription(
        Payment $payment,
        PaymentDetailsInterface $paymentDetails,
        ?bool $sendReceipt = null
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($payment, $paymentDetails, $sendReceipt) {
                $payment->setSendReceipt($sendReceipt ?? $this->options->get(Option::SEND_PAYMENT_RECEIPTS));
                $this->resolveCurrency($payment, $paymentDetails);
                $this->flushPaymentDetails($payment, $paymentDetails);
                $invoices = $this->resolvePaidInvoices($payment, []);
                $this->entityManager->persist($payment);
                $this->paymentCoversGenerator->processPayment($payment, $invoices);

                yield new PaymentAddEvent($payment);
            }
        );
    }

    public function handleSaveSubscription(PaymentPlan $paymentPlan): void
    {
        $this->entityManager->flush();
    }

    /**
     * @throws InvalidPaymentCurrencyException
     */
    public function handleUpdate(
        Payment $payment,
        Payment $paymentBeforeUpdate,
        array $invoices,
        bool $sendReceipt
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($payment, $paymentBeforeUpdate, $invoices, $sendReceipt) {
                if ($paymentBeforeUpdate->getOrganization() !== $payment->getClient()->getOrganization()) {
                    $payment->setReceiptNumber(null);
                }
                $payment->setSendReceipt($sendReceipt);

                $this->paymentCoversGenerator->processPayment($payment, $invoices);

                yield new PaymentEditEvent($payment, $paymentBeforeUpdate);
            }
        );
    }

    public function handleUpdateWithoutProcess(Payment $payment): void
    {
        $this->entityManager->flush();
    }

    public function handleDelete(Payment $payment): bool
    {
        $paymentId = $payment->getId();
        if (! $this->setDeleted($payment)) {
            return false;
        }

        $this->transactionDispatcher->transactional(
            function () use ($payment, $paymentId) {
                $hasClient = (bool) $payment->getClient();
                yield new PaymentDeleteEvent($payment, $paymentId);

                if ($hasClient) {
                    $this->serviceStatusUpdater->updateServices();
                    $this->clientStatusUpdater->updateDirectly();
                }
            }
        );

        return true;
    }

    /**
     * @param int[] $ids
     */
    public function handleDeleteMultipleByIds(array $ids): array
    {
        $payments = $this->entityManager->getRepository(Payment::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        return $this->handleDeleteMultiple($payments);
    }

    /**
     * @param Payment[] $payments
     */
    public function handleDeleteMultiple(array $payments): array
    {
        $count = count($payments);
        $deleted = 0;

        $this->transactionDispatcher->transactional(
            function () use ($payments, &$deleted) {
                foreach ($payments as $payment) {
                    $paymentId = $payment->getId();
                    if (! $this->setDeleted($payment)) {
                        continue;
                    }

                    yield new PaymentDeleteEvent($payment, $paymentId);

                    ++$deleted;
                }
            }
        );

        if ($deleted) {
            $this->serviceStatusUpdater->updateServices();
            $this->clientStatusUpdater->update();
        }

        return [$deleted, $count - $deleted];
    }

    public function handleUnmatch(Payment $payment): bool
    {
        $client = $payment->getClient();

        if (! $this->setUnmatched($payment)) {
            return false;
        }

        $this->transactionDispatcher->transactional(
            function () use ($payment, $client) {
                yield new PaymentUnmatchEvent($payment, $client);

                if ($client) {
                    $this->serviceStatusUpdater->updateServices();
                    $this->clientStatusUpdater->updateDirectly();
                }
            }
        );

        return true;
    }

    /**
     * @return array [$unmatched, $failed]
     */
    public function handleUnmatchMultiple(array $ids): array
    {
        $payments = $this->entityManager->getRepository(Payment::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        $count = count($payments);
        $unmatched = 0;

        $this->transactionDispatcher->transactional(
            function () use ($payments, &$unmatched) {
                foreach ($payments as $payment) {
                    $client = $payment->getClient();
                    if (! $this->setUnmatched($payment)) {
                        continue;
                    }

                    yield new PaymentUnmatchEvent($payment, $client);

                    ++$unmatched;
                }
            }
        );

        if ($unmatched) {
            $this->serviceStatusUpdater->updateServices();
            $this->clientStatusUpdater->update();
        }

        return [$unmatched, $count - $unmatched];
    }

    /**
     * Fixes payments which were wrongly unmatched due to a bug introduced in 2.3.0-beta.
     * We should remove this some time in the future, when no one is on version less then 2.3.0.
     * Called in BumpVersionCommand and only executed if previous version was 2.3.0-beta.
     *
     * After remove, check if is possible remove also deprecated ClientRepository::countAccountStandings()
     */
    public function fixWronglyUnmatchedPayments()
    {
        $unmatchedPayments = $this->entityManager->getRepository(Payment::class)->findBy(
            [
                'client' => null,
            ]
        );
        $unmatchIds = [];
        $clientIds = [];

        foreach ($unmatchedPayments as $payment) {
            $unmatchIds[] = $payment->getId();
            if ($payment->getCredit()) {
                $clientIds[] = $payment->getCredit()->getClient()->getId();
            }
        }

        if (! empty($unmatchIds)) {
            $this->handleUnmatchMultiple($unmatchIds);
            $this->entityManager->clear();
        }

        /** @var PaymentCover[] $possiblyWrongCovers */
        $possiblyWrongCovers = $this->entityManager->getRepository(PaymentCover::class)
            ->createQueryBuilder('pc')
            ->where('pc.invoice IS NOT NULL')
            ->getQuery()
            ->getResult();

        foreach ($possiblyWrongCovers as $cover) {
            $coverClient = $cover->getPayment()->getClient();
            $invoiceClient = $cover->getInvoice()->getClient();
            if ($coverClient === $invoiceClient) {
                continue;
            }

            if ($coverClient) {
                $clientIds[] = $coverClient->getId();
            }

            if ($invoiceClient) {
                $clientIds[] = $invoiceClient->getId();
            }

            $this->removeFromInvoice($cover->getPayment());
            $this->entityManager->remove($cover);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $clientIds = array_unique($clientIds);
        foreach ($clientIds as $clientId) {
            $client = $this->entityManager->find(Client::class, $clientId);

            foreach ($client->getPayments() as $payment) {
                if (! $payment->getCredit()) {
                    continue;
                }

                $credit = $payment->getCredit();
                $fractionDigits = $payment->getCurrency()->getFractionDigits();
                $amount = round($payment->getAmount(), $fractionDigits);

                foreach ($payment->getPaymentCovers() as $cover) {
                    if (null === $cover->getInvoice() && null === $cover->getRefund()) {
                        continue;
                    }

                    $amount -= $cover->getAmount();
                }

                if ($amount > 0.0) {
                    $credit->setAmount($amount);
                } else {
                    $payment->setCredit(null);
                    $this->entityManager->remove($credit);
                }
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        if (! empty($clientIds)) {
            $this->entityManager->getRepository(Client::class)->countAccountStandings($clientIds);
            $this->serviceStatusUpdater->updateServices();
            $this->clientStatusUpdater->update();
            $this->entityManager->clear();
        }
    }

    /**
     * Fixes duplicated credit.
     * We should remove this some time in the future, when no one is on version less then 2.5.0-beta2.
     * Called in BumpVersionCommand and only executed if previous version was between 2.3.0-beta1 and 2.5.0-beta2.
     *
     * After remove, check if is possible remove also deprecated ClientRepository::countAccountStandings()
     */
    public function fixWronglyCreatedCredit(): void
    {
        $payments = $this->entityManager->getRepository(Payment::class)->findAll();
        $clientIds = [];
        $invoiceIds = [];

        foreach ($payments as $payment) {
            $clientId = $payment->getClient() ? $payment->getClient()->getId() : null;
            if (! $clientId) {
                continue;
            }
            $fractionDigits = $payment->getCurrency()->getFractionDigits() ?: 2;
            $amount = round($payment->getAmount(), $fractionDigits);
            $coverAndCreditAmount = $this->getPaymentCoverAndCreditAmount($payment);

            $maxIterations = 100;
            while ($amount !== $coverAndCreditAmount) {
                if ($payment->getCredit()) {
                    $this->entityManager->remove($payment->getCredit());
                    $payment->setCredit(null);
                    $coverAndCreditAmount = $this->getPaymentCoverAndCreditAmount($payment);
                    $clientIds[] = $clientId;
                    continue;
                }

                $covers = $payment->getPaymentCovers()->toArray();
                usort(
                    $covers,
                    function (PaymentCover $a, PaymentCover $b) {
                        return $b->getId() <=> $a->getId();
                    }
                );

                /** @var PaymentCover $cover */
                $cover = reset($covers);
                if ($cover) {
                    if ($refund = $cover->getRefund()) {
                        $refund->setAmount($refund->getAmount() - $cover->getAmount());
                        $refund->getPaymentCovers()->removeElement($cover);
                        $payment->getPaymentCovers()->removeElement($cover);
                        $this->entityManager->remove($cover);
                        $coverAndCreditAmount = $this->getPaymentCoverAndCreditAmount($payment);
                        $clientIds[] = $clientId;
                        continue;
                    }

                    if ($invoice = $cover->getInvoice()) {
                        $invoiceAmountPaid = round($invoice->getAmountPaid() - $cover->getAmount(), $fractionDigits);
                        $invoice->setAmountPaid($invoiceAmountPaid);
                        $invoice->getPaymentCovers()->removeElement($cover);
                        $payment->getPaymentCovers()->removeElement($cover);
                        $this->entityManager->remove($cover);
                        $invoiceIds[] = $invoice->getId();
                        $coverAndCreditAmount = $this->getPaymentCoverAndCreditAmount($payment);
                        $clientIds[] = $clientId;
                        continue;
                    }
                }

                if (! $covers && ! $payment->getCredit()) {
                    break;
                }

                if (--$maxIterations <= 0) {
                    break;
                }
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        if (! empty($invoiceIds)) {
            foreach ($invoiceIds as $invoiceId) {
                $this->invoiceCalculations->recalculatePayments($this->entityManager->find(Invoice::class, $invoiceId));
            }
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        if (! empty($clientIds)) {
            $this->entityManager->getRepository(Client::class)->countAccountStandings($clientIds);
            $this->serviceStatusUpdater->updateServices();
            $this->clientStatusUpdater->update();
            $this->entityManager->clear();
        }
    }

    private function getPaymentCoverAndCreditAmount(Payment $payment): float
    {
        $coverAmount = 0.0;
        foreach ($payment->getPaymentCovers() as $cover) {
            $coverAmount += $cover->getAmount();
        }
        $creditAmount = $payment->getCredit() ? $payment->getCredit()->getAmount() : 0.0;

        return round($coverAmount + $creditAmount, $payment->getCurrency()->getFractionDigits() ?: 2);
    }

    private function removeFromInvoice(Payment $payment): void
    {
        foreach ($payment->getPaymentCovers() as $cover) {
            $invoice = $cover->getInvoice();
            if (! $invoice) {
                continue;
            }

            $fractionDigits = $invoice->getCurrency()->getFractionDigits();

            if (round($cover->getAmount(), $fractionDigits) === round($invoice->getAmountPaid(), $fractionDigits)) {
                $invoice->setInvoiceStatus(Invoice::UNPAID);
                $invoice->setAmountPaid(0);
            } else {
                $invoiceAmountPaid = round($invoice->getAmountPaid() - $cover->getAmount(), $fractionDigits);
                $invoice->setInvoiceStatus(Invoice::PARTIAL);
                $invoice->setAmountPaid($invoiceAmountPaid);
            }
        }
    }

    public function recalculateInvoicePayments(Invoice $invoice): void
    {
        if ($this->invoiceCalculations->recalculatePayments($invoice)) {
            $this->entityManager->flush();
        }
    }

    public function getGridPostFetchCallback(): callable
    {
        return function ($result) {
            $ids = array_map(
                function (array $row) {
                    return $row[0]->getId();
                },
                $result
            );

            $this->entityManager->getRepository(Payment::class)->loadRelatedEntities('paymentCovers', $ids);
        };
    }

    private function setDeleted(Payment $payment): bool
    {
        if (! $this->entityManager->getRepository(Payment::class)->isRemovalPossible($payment)) {
            return false;
        }

        $this->removeFromInvoice($payment);
        $this->entityManager->remove($payment);

        return true;
    }

    private function setUnmatched(Payment $payment): bool
    {
        if (! $this->entityManager->getRepository(Payment::class)->isRemovalPossible($payment)) {
            return false;
        }

        $this->removeFromInvoice($payment);

        foreach ($payment->getPaymentCovers() as $cover) {
            $this->entityManager->remove($cover);
        }

        if ($payment->getClient()) {
            $payment->setClient(null);
        }

        if ($credit = $payment->getCredit()) {
            // Needed because second flush would otherwise cascade persist the credit again.
            $payment->setCredit(null);
            $this->entityManager->remove($credit);
        }

        return true;
    }

    private function resolveCurrency(Payment $payment, ?PaymentDetailsInterface $paymentDetails = null): void
    {
        if (null === $payment->getCurrency() && $payment->getClient()) {
            $payment->setCurrency($payment->getClient()->getOrganization()->getCurrency());
        }

        if ($paymentDetails && $payment->getCurrency() && ! $paymentDetails->getCurrency()) {
            $paymentDetails->setCurrency($payment->getCurrency()->getCode());
        }
    }

    private function flushPaymentDetails(Payment $payment, PaymentDetailsInterface $paymentDetails): void
    {
        $this->entityManager->persist($paymentDetails);
        $this->entityManager->flush($paymentDetails);

        $payment->setProvider($this->entityManager->find(PaymentProvider::class, $paymentDetails->getProviderId()));
        $payment->setPaymentDetailsId($paymentDetails->getId());
    }

    private function resolvePaidInvoices(Payment $payment, array $invoices): array
    {
        if (! $payment->getClient()) {
            return [];
        }

        $invoices = array_unique(
            array_merge(
                $invoices,
                $this->entityManager->getRepository(Invoice::class)
                    ->getClientUnpaidInvoicesWithCurrency($payment->getClient(), $payment->getCurrency())
            ),
            SORT_REGULAR
        );

        return $invoices;
    }

    public function preparePdfDownload(string $name, array $ids, User $user): void
    {
        $this->prepareDownloads($name, $ids, $user, ExportPaymentOverviewMessage::FORMAT_PDF);
    }

    public function prepareCsvDownload(string $name, array $ids, User $user): void
    {
        $this->prepareDownloads($name, $ids, $user, ExportPaymentOverviewMessage::FORMAT_CSV);
    }

    public function prepareQuickBooksCsvDownload(string $name, array $ids, User $user): void
    {
        $this->prepareDownloads($name, $ids, $user, ExportPaymentOverviewMessage::FORMAT_QUICKBOOKS_CSV);
    }

    private function prepareDownloads(string $name, array $ids, User $user, string $filetype): void
    {
        $download = new Download();

        $this->entityManager->transactional(
            function () use ($download, $name, $user) {
                $download->setName($name);
                $download->setCreated(new \DateTime());
                $download->setStatus(Download::STATUS_PENDING);
                $download->setUser($user);

                $this->entityManager->persist($download);
            }
        );

        $this->rabbitMqEnqueuer->enqueue(new ExportPaymentOverviewMessage($download, $ids, $filetype));
    }

    public function prepareReceiptPdfDownload(string $name, array $ids, User $user): void
    {
        $download = new Download();

        $this->entityManager->transactional(
            function () use ($download, $name, $user) {
                $download->setName($name);
                $download->setCreated(new \DateTime());
                $download->setStatus(Download::STATUS_PENDING);
                $download->setUser($user);

                $this->entityManager->persist($download);
            }
        );

        $this->rabbitMqEnqueuer->enqueue(new ExportPaymentReceiptMessage($download, $ids));
    }
}
