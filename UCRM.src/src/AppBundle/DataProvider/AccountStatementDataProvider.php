<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Request\AccountStatementRequest;
use ApiBundle\Request\InvoiceCollectionRequest;
use ApiBundle\Request\PaymentCollectionRequest;
use ApiBundle\Request\RefundCollectionRequest;
use AppBundle\Component\AccountStatement\AccountStatement;
use AppBundle\Component\AccountStatement\AccountStatementItem;
use AppBundle\Component\Service\TimePeriod;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Option;
use AppBundle\Service\Client\ClientAccountStatementCalculator;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class AccountStatementDataProvider extends AbstractAccountStatementDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PaymentDataProvider
     */
    private $paymentDataProvider;

    /**
     * @var InvoiceDataProvider
     */
    private $invoiceDataProvider;

    /**
     * @var RefundDataProvider
     */
    private $refundDataProvider;

    /**
     * @var ClientAccountStatementCalculator
     */
    private $accountStatementCalculator;

    /**
     * @var Options
     */
    private $options;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaymentDataProvider $paymentDataProvider,
        InvoiceDataProvider $invoiceDataProvider,
        RefundDataProvider $refundDataProvider,
        ClientAccountStatementCalculator $accountStatementCalculator,
        Options $options
    ) {
        $this->entityManager = $entityManager;
        $this->paymentDataProvider = $paymentDataProvider;
        $this->invoiceDataProvider = $invoiceDataProvider;
        $this->refundDataProvider = $refundDataProvider;
        $this->accountStatementCalculator = $accountStatementCalculator;
        $this->options = $options;
    }

    public function getAccountStatement(AccountStatementRequest $request): AccountStatement
    {
        if (! $request->client) {
            throw new \InvalidArgumentException('Client is required.');
        }

        $accountStatement = new AccountStatement();
        $accountStatement->client = $request->client;
        $accountStatement->currency = $request->client->getOrganization()->getCurrency();
        $accountStatement->startDate = $request->startDate;
        $accountStatement->endDate = $request->endDate;
        $accountStatement->initialBalance = $this->getInitialBalance($request);
        $accountStatement->items = $this->getItems($request);

        $this->accountStatementCalculator->calculateBalances($accountStatement);

        return $accountStatement;
    }

    public function getAccountStatementByTimePeriod(
        Client $client,
        TimePeriod $timePeriodData
    ): AccountStatement {
        $request = new AccountStatementRequest($client);
        $request->startDate = $timePeriodData->startDate;
        $request->endDate = $timePeriodData->endDate;

        return $this->getAccountStatement($request);
    }

    /**
     * @return AccountStatementItem[]
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function getItems(AccountStatementRequest $request): array
    {
        $paymentRequest = new PaymentCollectionRequest();
        $paymentRequest->clientId = $request->client->getId();
        $paymentRequest->startDate = $request->startDate;
        $paymentRequest->endDate = $request->endDate;
        $paymentRequest->limit = $request->limit;
        $paymentRequest->offset = $request->offset;
        $paymentRequest->order = 'createdDate';
        $paymentRequest->direction = 'ASC';

        $refundRequest = new RefundCollectionRequest();
        $refundRequest->clientId = $request->client->getId();
        $refundRequest->startDate = $request->startDate;
        $refundRequest->endDate = $request->endDate;
        $refundRequest->limit = $request->limit;
        $refundRequest->offset = $request->offset;
        $refundRequest->order = 'createdDate';
        $refundRequest->direction = 'ASC';

        $invoiceRequest = new InvoiceCollectionRequest();
        $invoiceRequest->clientId = $request->client->getId();
        $invoiceRequest->startDate = $request->startDate;
        $invoiceRequest->endDate = $request->endDate;
        $invoiceRequest->limit = $request->limit;
        $invoiceRequest->offset = $request->offset;
        $invoiceRequest->statuses = Invoice::VALID_STATUSES;
        $invoiceRequest->order = 'createdDate';
        $invoiceRequest->direction = 'ASC';

        $payments = $this->paymentDataProvider->getCollection($paymentRequest);
        $invoices = $this->invoiceDataProvider->getInvoiceCollection($invoiceRequest);
        $refunds = $this->refundDataProvider->getCollection($refundRequest);

        return $this->convertToAccountStatementItems(
            $invoices,
            $payments,
            $refunds
        );
    }

    /**
     * Calculates initial standing, before the requested range (client might have older invoices/payments):
     * Client.User.createdAt <= startDate < endDate <= now().
     */
    private function getInitialBalance(AccountStatementRequest $request): float
    {
        if (! $request->startDate) {
            return 0.0;
        }

        $connection = $this->entityManager->getConnection();
        $clientId = $request->client->getId();
        $createdDate = DateTimeFactory::createFromInterface($request->startDate)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format($connection->getDatabasePlatform()->getDateTimeFormatString());

        $statement = $connection->executeQuery(
            'SELECT
              SUM(amount_part) AS amount
            FROM (
              SELECT
                SUM(amount) AS amount_part
              FROM payment
              WHERE
                client_id = ?
                AND created_date < ?
                
              UNION
              
              SELECT
                -SUM(amount) AS amount_part
              FROM refund
              WHERE 
                client_id = ?
                AND created_date < ?
              
              UNION
              
              SELECT 
                -SUM(total) AS amount_part
              FROM invoice
              WHERE
                client_id = ?
                AND created_date < ?
                AND invoice_status IN (?)
            ) sums',
            [
                $clientId,
                $createdDate,
                $clientId,
                $createdDate,
                $clientId,
                $createdDate,
                Invoice::VALID_STATUSES,
            ],
            [
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                Connection::PARAM_INT_ARRAY,
            ]
        );

        $initialBalance = (float) $statement->fetchColumn();
        if ($this->options->get(Option::BALANCE_STYLE) === Option::BALANCE_STYLE_TYPE_US) {
            $initialBalance *= -1;
        }

        return $initialBalance;
    }
}
