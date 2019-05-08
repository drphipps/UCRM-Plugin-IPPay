<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170821094621 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE INDEX IDX_6D28840DADE9C86E ON payment (created_date)');
        $this->addSql('CREATE INDEX IDX_5B2C1458ADE9C86E ON refund (created_date)');
        $this->addSql('CREATE INDEX IDX_9445CA1A5E237E06 ON organization_bank_account (name)');
        $this->addSql('CREATE INDEX IDX_54C9B0DF5E237E06 ON surcharge (name)');
        $this->addSql('CREATE INDEX IDX_C1EE637C5E237E06 ON organization (name)');
        $this->addSql('CREATE INDEX IDX_884845DB5E237E06 ON invoice_template (name)');
        $this->addSql('CREATE INDEX IDX_8E81BA765E237E06 ON tax (name)');
        $this->addSql('CREATE INDEX IDX_92FB68E5E237E06 ON device (name)');
        $this->addSql('CREATE INDEX IDX_781A8270B23DB7B8 ON download (created)');
        $this->addSql('CREATE INDEX IDX_9465207D5E237E06 ON tariff (name)');
        $this->addSql('CREATE INDEX IDX_694309E45E237E06 ON site (name)');
        $this->addSql('CREATE INDEX IDX_32E4644DADE9C86E ON header_notification (created_date)');
        $this->addSql('CREATE INDEX IDX_C0D14661BDFFA9F2 ON service_device_outage (outage_start)');
        $this->addSql('CREATE INDEX IDX_D34A04AD5E237E06 ON product (name)');
        $this->addSql('CREATE INDEX IDX_106EFCE3BDFFA9F2 ON device_outage (outage_start)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX IDX_6D28840DADE9C86E');
        $this->addSql('DROP INDEX IDX_5B2C1458ADE9C86E');
        $this->addSql('DROP INDEX IDX_694309E45E237E06');
        $this->addSql('DROP INDEX IDX_92FB68E5E237E06');
        $this->addSql('DROP INDEX IDX_D34A04AD5E237E06');
        $this->addSql('DROP INDEX IDX_9445CA1A5E237E06');
        $this->addSql('DROP INDEX IDX_54C9B0DF5E237E06');
        $this->addSql('DROP INDEX IDX_9465207D5E237E06');
        $this->addSql('DROP INDEX IDX_C1EE637C5E237E06');
        $this->addSql('DROP INDEX IDX_8E81BA765E237E06');
        $this->addSql('DROP INDEX IDX_106EFCE3BDFFA9F2');
        $this->addSql('DROP INDEX IDX_C0D14661BDFFA9F2');
        $this->addSql('DROP INDEX IDX_781A8270B23DB7B8');
        $this->addSql('DROP INDEX IDX_32E4644DADE9C86E');
        $this->addSql('DROP INDEX IDX_884845DB5E237E06');
    }
}
