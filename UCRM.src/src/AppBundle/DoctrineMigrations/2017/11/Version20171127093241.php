<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171127093241 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE quote_template (id SERIAL NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, name VARCHAR(255) NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, error_notification_sent BOOLEAN DEFAULT \'false\' NOT NULL, official_name VARCHAR(100) DEFAULT NULL, is_valid BOOLEAN DEFAULT \'true\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7D5AB7125E237E06 ON quote_template (name)');
        $this->addSql('COMMENT ON COLUMN quote_template.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN quote_template.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER SEQUENCE quote_template_id_seq RESTART WITH 1000');
        $this->addSql('INSERT INTO quote_template (id, name, created_date, official_name) VALUES (1, \'Default\', now(), \'default\')');
        $this->addSql('ALTER TABLE quote ADD quote_template_id INT NOT NULL');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF459D932C6 FOREIGN KEY (quote_template_id) REFERENCES quote_template (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6B71CBF459D932C6 ON quote (quote_template_id)');
        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'BACKUP_INCLUDE_QUOTE_TEMPLATES',
                1,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX IDX_6B71CBF459D932C6');
        $this->addSql('ALTER TABLE quote DROP quote_template_id');
        $this->addSql('DROP TABLE quote_template');
        $this->addSql(
            'DELETE FROM option WHERE code = ?',
            [
                'BACKUP_INCLUDE_QUOTE_TEMPLATES',
            ]
        );
    }
}
