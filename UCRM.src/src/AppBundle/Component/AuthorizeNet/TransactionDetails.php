<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\AuthorizeNet;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * Wrapper for Authorize.Net TD API.
 *
 * @see http://developer.authorize.net/api/reference/features/transaction_reporting.html
 * @see http://developer.authorize.net/api/reference/#transaction-reporting
 */
class TransactionDetails extends AuthorizeNetAPIAccess
{
    /**
     * @return array|AnetAPI\TransactionSummaryType[]
     *
     * @throws AuthorizeNetException
     */
    public function getSettledTransactionList(\DateTime $from, \DateTime $to): array
    {
        $transactions = [];
        $settledBatchList = $this->getSettledBatchList($from, $to);
        $merchantAuthentication = $this->getMerchantAuthentication();

        foreach ($settledBatchList as $batchDetails) {
            $request = new AnetAPI\GetTransactionListRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setBatchId($batchDetails->getBatchId());

            $controller = new AnetController\GetTransactionListController($request);
            $response = $this->executeWithApiResponse($controller);

            if (
                $response instanceof AnetAPI\GetTransactionListResponse
                && $response->getMessages()->getResultCode() === self::RESPONSE_OK
            ) {
                $transactions = array_merge($transactions, $response->getTransactions());
            } else {
                throw $this->createException($response);
            }
        }

        return $transactions;
    }

    /**
     * @return array|AnetAPI\TransactionSummaryType[]
     *
     * @throws AuthorizeNetException
     */
    public function getUnsettledTransactionList(): array
    {
        $request = new AnetAPI\GetUnsettledTransactionListRequest();
        $request->setMerchantAuthentication($this->getMerchantAuthentication());

        $controller = new AnetController\GetUnsettledTransactionListController($request);
        $response = $this->executeWithApiResponse($controller);

        if (
            $response instanceof AnetAPI\GetUnsettledTransactionListResponse
            && $response->getMessages()->getResultCode() === self::RESPONSE_OK
        ) {
            return $response->getTransactions() ?: [];
        }

        throw $this->createException($response);
    }

    /**
     * @return array|AnetAPI\BatchDetailsType[]
     *
     * @throws AuthorizeNetException
     */
    private function getSettledBatchList(\DateTime $from, \DateTime $to): array
    {
        $request = new AnetAPI\GetSettledBatchListRequest();
        $request->setMerchantAuthentication($this->getMerchantAuthentication());
        $request->setIncludeStatistics(false);
        $request->setFirstSettlementDate($from);
        $request->setLastSettlementDate($to);

        $controller = new AnetController\GetSettledBatchListController($request);
        $response = $this->executeWithApiResponse($controller);

        if (
            $response instanceof AnetAPI\GetSettledBatchListResponse
            && $response->getMessages()->getResultCode() === self::RESPONSE_OK
        ) {
            return $response->getBatchList() ?? [];
        }

        throw $this->createException($response);
    }
}
