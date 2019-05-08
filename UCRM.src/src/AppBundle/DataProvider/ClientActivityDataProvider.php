<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeFactory;
use Doctrine\DBAL\Connection;

class ClientActivityDataProvider
{
    public const ACTIVITY_TYPE_INVOICE = 1;
    public const ACTIVITY_TYPE_PAYMENT = 2;
    public const ACTIVITY_TYPE_REFUND = 3;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Options
     */
    private $options;

    public function __construct(Connection $connection, Options $options)
    {
        $this->connection = $connection;
        $this->options = $options;
    }

    public function getActivity(int $id, bool $getInvoices, bool $getPayments, bool $getRefunds): array
    {
        $queries = [];
        $parameters = [];

        if ($getInvoices) {
            $queries[] = '
                SELECT
                    ?::integer AS type,
                    invoice.invoice_id AS id,
                    invoice.created_date AS "createdDate",
                    invoice.due_date AS "dueDate",
                    invoice.total AS amount,
                    currency.code AS "currencyCode",
                    invoice.invoice_status AS "invoiceStatus",
                    NULL::integer AS "paymentMethod",
                    NULL::integer AS "refundMethod",
                    invoice.invoice_number AS "invoiceNumber"
                FROM invoice
                JOIN currency ON currency.currency_id = invoice.currency_id
                JOIN client ON client.client_id = invoice.client_id
                WHERE client.client_id = ?
                AND client.deleted_at IS NULL
            ';

            $parameters[] = self::ACTIVITY_TYPE_INVOICE;
            $parameters[] = $id;
        }

        if ($getPayments) {
            $queries[] = '
                SELECT
                    ?::integer AS type,
                    payment.payment_id AS id,
                    payment.created_date AS "createdDate",
                    NULL::timestamp AS "dueDate",
                    payment.amount,
                    currency.code AS "currencyCode",
                    NULL::integer AS "invoiceStatus",
                    payment.method AS "paymentMethod",
                    NULL::integer AS "refundMethod",
                    NULL AS "invoiceNumber"
                FROM payment
                JOIN currency ON currency.currency_id = payment.currency_id
                JOIN client ON client.client_id = payment.client_id
                WHERE client.client_id = ?
                AND client.deleted_at IS NULL
            ';

            $parameters[] = self::ACTIVITY_TYPE_PAYMENT;
            $parameters[] = $id;
        }

        if ($getRefunds) {
            $queries[] = '
                SELECT
                    ?::integer AS type,
                    refund.refund_id AS id,
                    refund.created_date AS "createdDate",
                    NULL::timestamp AS "dueDate",
                    refund.amount,
                    currency.code AS "currencyCode",
                    NULL::integer AS "invoiceStatus",
                    NULL::integer AS "paymentMethod",
                    refund.method AS "refundMethod",
                    NULL AS "invoiceNumber"
                FROM refund
                JOIN currency ON currency.currency_id = refund.currency_id
                JOIN client ON client.client_id = refund.client_id
                WHERE client.client_id = ?
                AND client.deleted_at IS NULL
            ';

            $parameters[] = self::ACTIVITY_TYPE_REFUND;
            $parameters[] = $id;
        }

        if (! $queries) {
            return [];
        }

        $query = implode(' UNION ALL ', $queries) . ' ORDER BY "createdDate" DESC, type ASC, id ASC';

        $result = $this->connection
            ->executeQuery($query, $parameters)
            ->fetchAll();

        $timezone = $this->options->get(Option::APP_TIMEZONE, 'UTC');

        foreach ($result as &$item) {
            $item['createdDate'] = DateTimeFactory::createDateFromUTC($item['createdDate'], $timezone);
            $item['dueDate'] = $item['dueDate']
                ? DateTimeFactory::createDateFromUTC($item['dueDate'], $timezone)
                : null;
        }

        return $result;
    }
}
