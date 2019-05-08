<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181112134620 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            '
              INSERT INTO 
                option(code, value) 
              VALUES 
                (:newCode, (SELECT value FROM option WHERE code = :oldCode))
            ',
            [
                'newCode' => 'SEND_ANONYMOUS_STATISTICS',
                'oldCode' => 'STATISTICS_CONFIGURATION_SSL',
            ]
        );

        $this->addSql(
            'DELETE FROM option WHERE code IN (:codes)',
            [
                'codes' => [
                    'STATISTICS_CONFIGURATION_2FA',
                    'STATISTICS_CONFIGURATION_APP_KEYS',
                    'STATISTICS_CONFIGURATION_FEES',
                    'STATISTICS_CONFIGURATION_GENERAL',
                    'STATISTICS_CONFIGURATION_INVOICING',
                    'STATISTICS_CONFIGURATION_LOCALIZATION',
                    'STATISTICS_CONFIGURATION_MAILER',
                    'STATISTICS_CONFIGURATION_PAYMENT_GATEWAYS',
                    'STATISTICS_CONFIGURATION_SCHEDULING',
                    'STATISTICS_CONFIGURATION_SHORTCUTS',
                    'STATISTICS_CONFIGURATION_SSL',
                    'STATISTICS_CONFIGURATION_SUSPENSION',
                    'STATISTICS_CONFIGURATION_TICKETING',
                    'STATISTICS_COUNT_ADMINS',
                    'STATISTICS_COUNT_CLIENTS',
                    'STATISTICS_COUNT_INVOICES',
                    'STATISTICS_COUNT_JOBS',
                    'STATISTICS_COUNT_ORGANIZATIONS',
                    'STATISTICS_COUNT_TICKETS',
                ],
            ],
            [
                'codes' => Connection::PARAM_STR_ARRAY,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            '
              INSERT INTO 
                option(code, value) 
              VALUES 
                (:stat1, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat2, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat3, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat4, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat5, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat6, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat7, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat8, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat9, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat10, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat11, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat12, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat13, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat14, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat15, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat16, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat17, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat18, (SELECT value FROM option WHERE code = :oldCode)),
                (:stat19, (SELECT value FROM option WHERE code = :oldCode))
            ',
            [
                'stat1' => 'STATISTICS_CONFIGURATION_2FA',
                'stat2' => 'STATISTICS_CONFIGURATION_APP_KEYS',
                'stat3' => 'STATISTICS_CONFIGURATION_FEES',
                'stat4' => 'STATISTICS_CONFIGURATION_GENERAL',
                'stat5' => 'STATISTICS_CONFIGURATION_INVOICING',
                'stat6' => 'STATISTICS_CONFIGURATION_LOCALIZATION',
                'stat7' => 'STATISTICS_CONFIGURATION_MAILER',
                'stat8' => 'STATISTICS_CONFIGURATION_PAYMENT_GATEWAYS',
                'stat9' => 'STATISTICS_CONFIGURATION_SCHEDULING',
                'stat10' => 'STATISTICS_CONFIGURATION_SHORTCUTS',
                'stat11' => 'STATISTICS_CONFIGURATION_SSL',
                'stat12' => 'STATISTICS_CONFIGURATION_SUSPENSION',
                'stat13' => 'STATISTICS_CONFIGURATION_TICKETING',
                'stat14' => 'STATISTICS_COUNT_ADMINS',
                'stat15' => 'STATISTICS_COUNT_CLIENTS',
                'stat16' => 'STATISTICS_COUNT_INVOICES',
                'stat17' => 'STATISTICS_COUNT_JOBS',
                'stat18' => 'STATISTICS_COUNT_ORGANIZATIONS',
                'stat19' => 'STATISTICS_COUNT_TICKETS',
                'oldCode' => 'SEND_ANONYMOUS_STATISTICS',
            ]
        );

        $this->addSql(
            'DELETE FROM option WHERE code = :code',
            [
                'code' => 'SEND_ANONYMOUS_STATISTICS',
            ]
        );
    }
}
