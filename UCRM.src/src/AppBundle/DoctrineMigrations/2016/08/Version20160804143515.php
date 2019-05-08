<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160804143515 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ADD paypal_sandbox_client_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD paypal_sandbox_client_secret VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD stripe_test_secret_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD stripe_test_publishable_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD anet_sandbox_login_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD anet_sandbox_transaction_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD anet_sandbox_hash VARCHAR(20) DEFAULT NULL');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (34, 1, \'AppBundle\Controller\SandboxTerminationController\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP paypal_sandbox_client_id');
        $this->addSql('ALTER TABLE organization DROP paypal_sandbox_client_secret');
        $this->addSql('ALTER TABLE organization DROP stripe_test_secret_key');
        $this->addSql('ALTER TABLE organization DROP stripe_test_publishable_key');
        $this->addSql('ALTER TABLE organization DROP anet_sandbox_login_id');
        $this->addSql('ALTER TABLE organization DROP anet_sandbox_transaction_key');
        $this->addSql('ALTER TABLE organization DROP anet_sandbox_hash');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 34');
    }
}
