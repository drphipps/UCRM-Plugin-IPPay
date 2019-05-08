<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190108124621 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE invoice SET invoice_maturity_days = 36500 WHERE invoice_maturity_days IS NOT NULL AND invoice_maturity_days > 36500');
        $this->addSql('UPDATE client SET invoice_maturity_days = 36500 WHERE invoice_maturity_days IS NOT NULL AND invoice_maturity_days > 36500');
        $this->addSql('UPDATE organization SET invoice_maturity_days = 36500 WHERE invoice_maturity_days IS NOT NULL AND invoice_maturity_days > 36500');
        $this->addSql('UPDATE invoice SET due_date = due_date + (3000 - EXTRACT(YEAR FROM due_date) || \' years\')::interval WHERE due_date > \'9999-12-31 00:00:00\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
    }
}
