<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180326105830 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_plan RENAME COLUMN autopay TO linked');
        $this->addSql(
            'UPDATE option SET code = ? WHERE code = ?',
            [
                'SUBSCRIPTIONS_ENABLED_CUSTOM',
                'RECURRING_PAYMENTS_ENABLED',
            ]
        );
        $this->addSql(
            'UPDATE option SET code = ? WHERE code = ?',
            [
                'SUBSCRIPTIONS_ENABLED_LINKED',
                'RECURRING_PAYMENTS_AUTOPAY_ENABLED',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_plan RENAME COLUMN linked TO autopay');
        $this->addSql(
            'UPDATE option SET code = ? WHERE code = ?',
            [
                'RECURRING_PAYMENTS_ENABLED',
                'SUBSCRIPTIONS_ENABLED_CUSTOM',
            ]
        );
        $this->addSql(
            'UPDATE option SET code = ? WHERE code = ?',
            [
                'RECURRING_PAYMENTS_AUTOPAY_ENABLED',
                'SUBSCRIPTIONS_ENABLED_LINKED',
            ]
        );
    }
}
