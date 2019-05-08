<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180413075619 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_plan_unsubscribe DROP CONSTRAINT FK_1E0D92DBF1D00C71');
        $this->addSql('ALTER TABLE payment_plan_unsubscribe ADD CONSTRAINT FK_1E0D92DBF1D00C71 FOREIGN KEY (payment_plan_id) REFERENCES payment_plan (payment_plan_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_plan_unsubscribe DROP CONSTRAINT fk_1e0d92dbf1d00c71');
        $this->addSql('ALTER TABLE payment_plan_unsubscribe ADD CONSTRAINT fk_1e0d92dbf1d00c71 FOREIGN KEY (payment_plan_id) REFERENCES payment_plan (payment_plan_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
