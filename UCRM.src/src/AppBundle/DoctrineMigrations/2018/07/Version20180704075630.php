<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180704075630 extends AbstractMigration
{
    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql(
            'CREATE TABLE account_statement_template (id SERIAL NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, name VARCHAR(255) NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, error_notification_sent BOOLEAN DEFAULT \'false\' NOT NULL, official_name VARCHAR(100) DEFAULT NULL, is_valid BOOLEAN DEFAULT \'true\' NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('ALTER SEQUENCE account_statement_template_id_seq RESTART WITH 1000');

        $this->addSql('CREATE INDEX IDX_3C3AC3B55E237E06 ON account_statement_template (name)');
        $this->addSql('COMMENT ON COLUMN account_statement_template.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN account_statement_template.created_date IS \'(DC2Type:datetime_utc)\'');

        $this->addSql('INSERT INTO account_statement_template (id, name, created_date, official_name) VALUES (1, \'Default\', now(), \'default\')');

        $this->addSql('ALTER TABLE organization ADD account_statement_template_id INT DEFAULT NULL');
        $this->addSql('UPDATE organization SET account_statement_template_id = 1');
        $this->addSql('ALTER TABLE organization ALTER account_statement_template_id SET NOT NULL');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C830FA4C8 FOREIGN KEY (account_statement_template_id) REFERENCES account_statement_template (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C1EE637C830FA4C8 ON organization (account_statement_template_id)');
        $this->addSql('INSERT INTO user_group_permission (group_id, module_name, permission) VALUES (1, \'AppBundle\Controller\AccountStatementTemplateController\', \'edit\')');

        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'BACKUP_INCLUDE_ACCOUNT_STATEMENT_TEMPLATES',
                1,
            ]
        );
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );
        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637C830FA4C8');
        $this->addSql('DROP INDEX IDX_C1EE637C830FA4C8');
        $this->addSql('ALTER TABLE organization DROP account_statement_template_id');

        $this->addSql('DROP TABLE account_statement_template');
        $this->addSql('DELETE FROM user_group_permission WHERE module_name=\'AppBundle\Controller\AccountStatementTemplateController\'');
        $this->addSql(
            'DELETE FROM option WHERE code = ?',
            [
                'BACKUP_INCLUDE_ACCOUNT_STATEMENT_TEMPLATES',
            ]
        );
    }
}
