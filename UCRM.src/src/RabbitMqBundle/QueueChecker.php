<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace RabbitMqBundle;

use AppBundle\Util\Helpers;
use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;

class QueueChecker extends BaseAmqp
{
    private const CHECKED_RABBIT_QUEUES = [
        'delete_invoices',
        'delete_tickets',
        'export_invoices',
        'export_quotes',
        'export_clients',
        'export_client_logs_view',
        'export_invoice_overview',
        'export_quote_overview',
        'export_job',
        'export_payment_overview',
        'export_payment_receipt',
        'approve_draft',
        'generate_drafts',
        'initialize_draft_generation',
        'send_invoice',
        'fcc_report',
        'fcc_block_id',
        'synchronize_job_to_google_calendar',
        'send_email',
        'report_data_usage_generate',
        'webhook_event_request',
        'backup_sync_request',
        'payment_plan_cancel',
        'payment_plan_update_amount',
        'payment_plan_unlink',
        'client_import',
        'send_invitation_emails',
    ];

    public function areAllEmpty(): bool
    {
        if (Helpers::isDemo()) {
            return true;
        }

        foreach (self::CHECKED_RABBIT_QUEUES as $queueName) {
            if (! $this->isEmpty($queueName)) {
                return false;
            }
        }

        return true;
    }

    private function isEmpty(string $queueName): bool
    {
        try {
            $declareOk = $this->getChannel()->queue_declare($queueName, true);
        } catch (AMQPExceptionInterface $exception) {
            return $exception instanceof AMQPProtocolChannelException && $exception->getCode() === 404;
        }

        return is_array($declareOk) && array_key_exists(1, $declareOk) && $declareOk[1] === 0;
    }
}
