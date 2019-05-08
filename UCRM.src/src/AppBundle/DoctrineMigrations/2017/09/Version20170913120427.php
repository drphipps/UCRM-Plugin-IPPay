<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170913120427 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $defaultValue = $this->connection->fetchColumn(
            'SELECT COUNT(general_id) FROM general WHERE code IN (?, ?, ?) AND value = \'1\'',
            [
                'cmr_api_send_count_clients',
                'crm_api_send_count_organizations',
                'crm_api_send_count_invoices',
            ]
        );
        // Only allow sending statistic values automatically if all the previous statistics were enabled.
        $defaultValue = (int) ($defaultValue === 3);

        $this->addSql(
            'INSERT INTO 
                option (code, value)
             VALUES 
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?),
                (?, ?)
            ',
            [
                'STATISTICS_COUNT_CLIENTS',
                $defaultValue,
                'STATISTICS_COUNT_ORGANIZATIONS',
                $defaultValue,
                'STATISTICS_COUNT_INVOICES',
                $defaultValue,
                'STATISTICS_CONFIGURATION_2FA',
                $defaultValue,
                'STATISTICS_CONFIGURATION_APP_KEYS',
                $defaultValue,
                'STATISTICS_CONFIGURATION_FEES',
                $defaultValue,
                'STATISTICS_CONFIGURATION_GENERAL',
                $defaultValue,
                'STATISTICS_CONFIGURATION_INVOICING',
                $defaultValue,
                'STATISTICS_CONFIGURATION_LOCALIZATION',
                $defaultValue,
                'STATISTICS_CONFIGURATION_MAILER',
                $defaultValue,
                'STATISTICS_CONFIGURATION_PAYMENT_GATEWAYS',
                $defaultValue,
                'STATISTICS_CONFIGURATION_SCHEDULING',
                $defaultValue,
                'STATISTICS_CONFIGURATION_SSL',
                $defaultValue,
                'STATISTICS_CONFIGURATION_SUSPENSION',
                $defaultValue,
                'STATISTICS_CONFIGURATION_TICKETING',
                $defaultValue,
                'STATISTICS_COUNT_JOBS',
                $defaultValue,
                'STATISTICS_COUNT_TICKETS',
                $defaultValue,
            ]
        );

        $this->addSql(
            'DELETE FROM general WHERE code IN (?, ?, ?)',
            [
                'cmr_api_send_count_clients',
                'crm_api_send_count_organizations',
                'crm_api_send_count_invoices',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'INSERT INTO 
                general (code, value)
             VALUES (?, COALESCE((SELECT option.value FROM option WHERE option.code = ?), \'0\'))',
            [
                'cmr_api_send_count_clients',
                'STATISTICS_COUNT_CLIENTS',
            ]
        );
        $this->addSql(
            'INSERT INTO 
                general (code, value)
             VALUES (?, COALESCE((SELECT option.value FROM option WHERE option.code = ?), \'0\'))',
            [
                'crm_api_send_count_organizations',
                'STATISTICS_COUNT_ORGANIZATIONS',
            ]
        );
        $this->addSql(
            'INSERT INTO 
                general (code, value)
             VALUES (?, COALESCE((SELECT option.value FROM option WHERE option.code = ?), \'0\'))',
            [
                'crm_api_send_count_invoices',
                'STATISTICS_COUNT_INVOICES',
            ]
        );

        $this->addSql(
            'DELETE FROM option WHERE code IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                'STATISTICS_CONFIGURATION_2FA',
                'STATISTICS_CONFIGURATION_APP_KEYS',
                'STATISTICS_CONFIGURATION_FEES',
                'STATISTICS_CONFIGURATION_GENERAL',
                'STATISTICS_CONFIGURATION_INVOICING',
                'STATISTICS_CONFIGURATION_LOCALIZATION',
                'STATISTICS_CONFIGURATION_MAILER',
                'STATISTICS_CONFIGURATION_PAYMENT_GATEWAYS',
                'STATISTICS_CONFIGURATION_SCHEDULING',
                'STATISTICS_CONFIGURATION_SSL',
                'STATISTICS_CONFIGURATION_SUSPENSION',
                'STATISTICS_CONFIGURATION_TICKETING',
                'STATISTICS_COUNT_CLIENTS',
                'STATISTICS_COUNT_INVOICES',
                'STATISTICS_COUNT_JOBS',
                'STATISTICS_COUNT_ORGANIZATIONS',
                'STATISTICS_COUNT_TICKETS',
            ]
        );
    }
}
