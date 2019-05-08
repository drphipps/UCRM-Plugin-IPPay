<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20181002095248 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE credit DROP CONSTRAINT FK_1CC16EFE4C3A3BB');
        $this->addSql('ALTER TABLE credit ADD CONSTRAINT FK_1CC16EFE4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (payment_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE credit DROP CONSTRAINT fk_1cc16efe4c3a3bb');
        $this->addSql('ALTER TABLE credit ADD CONSTRAINT fk_1cc16efe4c3a3bb FOREIGN KEY (payment_id) REFERENCES payment (payment_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
