<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171127144920 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ADD quote_template_id INT NULL');
        $this->addSql('UPDATE organization SET quote_template_id = 1');
        $this->addSql('ALTER TABLE organization ALTER quote_template_id SET NOT NULL');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C59D932C6 FOREIGN KEY (quote_template_id) REFERENCES quote_template (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C1EE637C59D932C6 ON organization (quote_template_id)');
        $this->addSql('INSERT INTO user_group_permission (group_id, module_name, permission) VALUES (1, \'AppBundle\Controller\QuoteTemplateController\', \'edit\')');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637C59D932C6');
        $this->addSql('DROP INDEX IDX_C1EE637C59D932C6');
        $this->addSql('ALTER TABLE organization DROP quote_template_id');
        $this->addSql('DELETE FROM user_group_permission WHERE module_name=\'AppBundle\Controller\QuoteTemplateController\'');
    }
}
