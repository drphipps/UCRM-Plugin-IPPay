<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180820075146 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE draft_generation_item (id SERIAL NOT NULL, draft_generation_id INT NOT NULL, invoice_id INT NOT NULL, draft BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_953797BEAD3231B6 ON draft_generation_item (draft_generation_id)');
        $this->addSql('CREATE INDEX IDX_953797BE2989F1FD ON draft_generation_item (invoice_id)');
        $this->addSql('CREATE TABLE draft_generation (id SERIAL NOT NULL, uuid UUID NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, count INT DEFAULT 0 NOT NULL, count_success INT DEFAULT 0 NOT NULL, count_failure INT DEFAULT 0 NOT NULL, send_notification BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EC731D90D17F50A6 ON draft_generation (uuid)');
        $this->addSql('COMMENT ON COLUMN draft_generation.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE draft_generation_item ADD CONSTRAINT FK_953797BEAD3231B6 FOREIGN KEY (draft_generation_id) REFERENCES draft_generation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE draft_generation_item ADD CONSTRAINT FK_953797BE2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE draft_generation_item DROP CONSTRAINT FK_953797BEAD3231B6');
        $this->addSql('DROP TABLE draft_generation_item');
        $this->addSql('DROP TABLE draft_generation');
    }
}
