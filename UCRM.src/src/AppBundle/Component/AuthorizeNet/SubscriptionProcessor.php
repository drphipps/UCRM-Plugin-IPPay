<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\AuthorizeNet;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentAuthorizeNet;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Repository\PaymentPlanRepository;
use AppBundle\Service\Options;
use AppBundle\Util\DateFormats;
use Doctrine\ORM\EntityManager;
use net\authorize\api\contract\v1 as AnetAPI;
use Psr\Log\LoggerInterface;

class SubscriptionProcessor
{
    public const TRANSACTION_SETTLED_SUCCESSFULLY = 'settledSuccessfully';
    public const TRANSACTION_CAPTURED_PENDING_SETTLEMENT = 'capturedPendingSettlement';

    /**
     * @var TransactionDetailsFactory
     */
    private $transactionDetailsFactory;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PaymentPlanRepository
     */
    private $paymentPlanRepository;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    public function __construct(
        TransactionDetailsFactory $transactionDetailsFactory,
        EntityManager $em,
        Options $options,
        PaymentFacade $paymentFacade,
        LoggerInterface $logger,
        OptionsFacade $optionsFacade
    ) {
        $this->transactionDetailsFactory = $transactionDetailsFactory;
        $this->em = $em;
        $this->options = $options;
        $this->paymentFacade = $paymentFacade;
        $this->logger = $logger;

        $this->paymentPlanRepository = $this->em->getRepository(PaymentPlan::class);
        $this->optionsFacade = $optionsFacade;
    }

    public function process()
    {
        $isSandbox = (bool) $this->options->getGeneral(General::SANDBOX_MODE);
        $organizations = $this->em->getRepository(Organization::class)->findAll();

        $this->logger->info('Starting Authorize.Net subscription processor.');

        [$from, $to] = $this->getListBoundaries();

        foreach ($organizations as $organization) {
            if (! $organization->getAnetLoginId($isSandbox) || ! $organization->getAnetTransactionKey($isSandbox)) {
                continue;
            }

            $td = $this->transactionDetailsFactory->create($organization, $isSandbox);
            $this->logger->info(sprintf('Processing organization ID %d.', $organization->getId()));

            try {
                $settledTransactions = $td->getSettledTransactionList($from, $to);
                $this->processSettledTransactions($settledTransactions);
            } catch (AuthorizeNetException $e) {
                $this->logger->error($e->getMessage());
            }

            try {
                $unsettledTransactions = $td->getUnsettledTransactionList();
                $this->processUnsettledTransactions($unsettledTransactions);
            } catch (AuthorizeNetException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $this->optionsFacade->updateGeneral(General::ANET_LAST_BATCH_LIST, $to->format(DateFormats::ISO8601_Z));

        $this->logger->info('Done processing Authorize.Net subscriptions.');
    }

    /**
     * @param array|AnetAPI\TransactionSummaryType[] $transactions
     */
    private function processSettledTransactions(array $transactions)
    {
        $this->logger->info(sprintf('Found %d settled transactions.', count($transactions)));

        foreach ($transactions as $transaction) {
            if (
                $transaction->getTransactionStatus() !== self::TRANSACTION_SETTLED_SUCCESSFULLY
                || ! $transaction->getSubscription()
            ) {
                continue;
            }

            $this->processTransaction($transaction);
        }
    }

    /**
     * @param array|AnetAPI\TransactionSummaryType[] $transactions
     */
    private function processUnsettledTransactions(array $transactions)
    {
        $this->logger->info(sprintf('Found %d unsettled transactions.', count($transactions)));

        foreach ($transactions as $transaction) {
            if (
                $transaction->getTransactionStatus() !== self::TRANSACTION_CAPTURED_PENDING_SETTLEMENT
                || ! $transaction->getSubscription()
            ) {
                continue;
            }

            $this->processTransaction($transaction);
        }
    }

    /**
     * @return array|\DateTime[]
     */
    private function getListBoundaries(): array
    {
        $tz = new \DateTimeZone('UTC');
        $lastBatchList = $this->options->getGeneral(General::ANET_LAST_BATCH_LIST);
        $from = new \DateTime($lastBatchList ?: '-7 days', $tz);
        if ($lastBatchList) {
            // Make some overlap to cover possibly not included transactions.
            $from->modify('-1 day');
        }

        return [
            $from,
            new \DateTime('now', $tz),
        ];
    }

    /**
     * @throws \Exception
     */
    private function processTransaction(AnetAPI\TransactionSummaryType $transaction)
    {
        $subscriptionId = $transaction->getSubscription()->getId();
        /** @var PaymentPlan $paymentPlan */
        $paymentPlan = $this->paymentPlanRepository->findOneBy(
            [
                'providerSubscriptionId' => $subscriptionId,
                'provider' => PaymentPlan::PROVIDER_ANET,
                'active' => true,
            ]
        );

        if (! $paymentPlan) {
            $this->logger->notice(
                sprintf('No active payment plan found for transaction ID %d.', $transaction->getTransId())
            );

            return;
        }

        $client = $paymentPlan->getClient();
        $organization = $client->getOrganization();
        $currency = $organization->getCurrency();

        $exists = $this->em->getRepository(PaymentAuthorizeNet::class)->findOneBy(
            [
                'anetId' => $transaction->getTransId(),
            ]
        );

        if ($exists) {
            $this->logger->notice(
                sprintf('Transaction ID %d already processed, skipping.', $transaction->getTransId())
            );

            return;
        }

        $paymentAnet = new PaymentAuthorizeNet();
        $paymentAnet->setOrganization($organization);
        $paymentAnet->setClient($client);
        $paymentAnet->setAnetId($transaction->getTransId());
        $paymentAnet->setAmount($transaction->getSettleAmount());

        $payment = new Payment();
        $payment->setMethod(Payment::METHOD_AUTHORIZE_NET_SUBSCRIPTION);
        $payment->setCreatedDate(new \DateTime());
        $payment->setAmount(round($paymentAnet->getAmount(), $currency->getFractionDigits()));
        $payment->setClient($client);
        $payment->setCurrency($currency);

        $invoices = $this->em->getRepository(Invoice::class)->getClientUnpaidInvoicesWithCurrency(
            $paymentPlan->getClient(),
            $currency
        );

        $this->paymentFacade->handleCreate($payment, $invoices, $paymentAnet);
        $this->logger->info(sprintf('Transaction ID %d successfully processed.', $transaction->getTransId()));
    }
}
