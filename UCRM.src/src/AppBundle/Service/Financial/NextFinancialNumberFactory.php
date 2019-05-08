<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

use AppBundle\Entity\Organization;
use AppBundle\Util\Invoicing;
use Doctrine\DBAL\Connection;
use Nette\Utils\Strings;

class NextFinancialNumberFactory
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function createInvoiceNumber(Organization $organization): string
    {
        $prefix = Invoicing::printFinancialPrefix($organization->getInvoiceNumberPrefix() ?? '');
        $lastInvoiceNumber = $this->getMaxInvoiceNumberWithSamePrefix(
            $organization->getId(),
            $prefix,
            false
        );

        return Invoicing::getNextFinancialNumber(
            $organization->getInvoiceNumberLength(),
            $organization->getInvoiceNumberPrefix(),
            $organization->getInvoiceInitNumber(),
            $lastInvoiceNumber
        );
    }

    public function createQuoteNumber(Organization $organization): string
    {
        $prefix = Invoicing::printFinancialPrefix($organization->getQuoteNumberPrefix() ?? '');
        $lastQuoteNumber = $this->getMaxQuoteNumberWithSamePrefix(
            $organization->getId(),
            $prefix
        );

        return Invoicing::getNextFinancialNumber(
            $organization->getQuoteNumberLength(),
            $organization->getQuoteNumberPrefix(),
            $organization->getQuoteInitNumber(),
            $lastQuoteNumber
        );
    }

    public function createProformaInvoiceNumber(Organization $organization): string
    {
        $lastProformaInvoiceNumber = $this->getMaxInvoiceNumberWithSamePrefix(
            $organization->getId(),
            Invoicing::printFinancialPrefix($organization->getProformaInvoiceNumberPrefix() ?? ''),
            true
        );

        return Invoicing::getNextFinancialNumber(
            $organization->getProformaInvoiceNumberLength(),
            $organization->getProformaInvoiceNumberPrefix(),
            $organization->getProformaInvoiceInitNumber(),
            $lastProformaInvoiceNumber
        );
    }

    public function createReceiptNumber(Organization $organization): string
    {
        return Invoicing::getNextFinancialNumber(
            $organization->getReceiptNumberLength(),
            $organization->getReceiptNumberPrefix(),
            $organization->getReceiptInitNumber(),
            $this->getMaxReceiptNumberWithSamePrefix($organization)
        );
    }

    /**
     * Returns max invoice number with given prefix used within the organization.
     */
    private function getMaxInvoiceNumberWithSamePrefix(
        int $organizationId,
        string $printedPrefix,
        bool $isProforma
    ): ?int {
        $length = Strings::length($printedPrefix);

        $result = $this->connection->executeQuery(
            '
                SELECT MAX(SUBSTRING(i.invoice_number, 1 + :length)::bigint)
                FROM invoice i
                INNER JOIN client c ON c.client_id = i.client_id
                WHERE c.organization_id = :organizationId
                AND SUBSTRING(i.invoice_number, 1, :length) = :printedPrefix
                AND SUBSTRING(i.invoice_number, 1 + :length) ~ \'^[0-9]+$\'
                AND i.is_proforma = :isProforma
            ',
            [
                'length' => $length,
                'organizationId' => $organizationId,
                'printedPrefix' => $printedPrefix,
                'isProforma' => $isProforma,
            ],
            [
                'isProforma' => \PDO::PARAM_BOOL,
            ]
        )->fetchColumn();

        return $result === null ? null : (int) $result;
    }

    /**
     * Returns max quote number with given prefix used within the organization.
     */
    private function getMaxQuoteNumberWithSamePrefix(int $organizationId, string $printedPrefix): ?int
    {
        $length = Strings::length($printedPrefix);

        // MAX returns either an integer or null if there are no results.
        $result = $this->connection->fetchColumn(
            '
                SELECT MAX(SUBSTRING(q.quote_number, 1 + ?)::bigint)
                FROM quote q
                INNER JOIN client c ON c.client_id = q.client_id
                WHERE c.organization_id = ?
                AND SUBSTRING(q.quote_number, 1, ?) = ?
                AND SUBSTRING(q.quote_number, 1 + ?) ~ \'^[0-9]+$\'
            ',
            [
                $length,
                $organizationId,
                $length,
                $printedPrefix,
                $length,
            ]
        );

        return $result === null ? null : (int) $result;
    }

    /**
     * Returns max receipt number with given prefix used within the organization.
     */
    private function getMaxReceiptNumberWithSamePrefix(Organization $organization): ?int
    {
        $printedPrefix = Invoicing::printFinancialPrefix($organization->getReceiptNumberPrefix() ?? '');

        // MAX returns either an integer or null if there are no results.
        $result = $this->connection->fetchColumn(
            '
                SELECT MAX(SUBSTRING(p.receipt_number, 1 + :length)::bigint)
                FROM payment p
                LEFT JOIN client c ON c.client_id = p.client_id
                WHERE (p.organization_id = :organizationId OR c.organization_id = :organizationId)
                AND SUBSTRING(p.receipt_number, 1, :length) = :printedPrefix
                AND SUBSTRING(p.receipt_number, 1 + :length) ~ \'^[0-9]+$\'
            ',
            [
                'length' => Strings::length($printedPrefix),
                'organizationId' => $organization->getId(),
                'printedPrefix' => $printedPrefix,
            ]
        );

        return $result === null ? null : (int) $result;
    }
}
