<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180313114241 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_plan ADD autopay BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('INSERT INTO option (code, value) VALUES (\'RECURRING_PAYMENTS_AUTOPAY_ENABLED\', 0)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_plan DROP autopay');
        $this->addSql('DELETE FROM option WHERE code = \'RECURRING_PAYMENTS_AUTOPAY_ENABLED\'');
    }
}
