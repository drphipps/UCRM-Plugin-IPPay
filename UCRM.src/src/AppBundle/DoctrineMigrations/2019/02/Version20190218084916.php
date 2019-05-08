<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190218084916 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE report_data_usage ALTER current_period_start DROP NOT NULL');
        $this->addSql('ALTER TABLE report_data_usage ALTER current_period_end DROP NOT NULL');
        $this->addSql('ALTER TABLE report_data_usage ALTER current_period_download DROP NOT NULL');
        $this->addSql('ALTER TABLE report_data_usage ALTER current_period_upload DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE report_data_usage ALTER current_period_start SET NOT NULL');
        $this->addSql('ALTER TABLE report_data_usage ALTER current_period_end SET NOT NULL');
        $this->addSql('ALTER TABLE report_data_usage ALTER current_period_download SET NOT NULL');
        $this->addSql('ALTER TABLE report_data_usage ALTER current_period_upload SET NOT NULL');
    }
}
