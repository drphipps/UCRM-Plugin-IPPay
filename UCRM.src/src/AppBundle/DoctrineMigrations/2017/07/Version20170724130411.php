<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170724130411 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            '
                UPDATE organization
                SET
                    anet_login_id = substring(anet_login_id FROM 1 FOR 20),
                    anet_transaction_key = substring(anet_transaction_key FROM 1 FOR 16),
                    anet_sandbox_login_id = substring(anet_sandbox_login_id FROM 1 FOR 20),
                    anet_sandbox_transaction_key = substring(anet_sandbox_transaction_key FROM 1 FOR 16)
                ;
            '
        );

        $this->addSql('ALTER TABLE organization ALTER anet_login_id TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE organization ALTER anet_transaction_key TYPE VARCHAR(16)');
        $this->addSql('ALTER TABLE organization ALTER anet_sandbox_login_id TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE organization ALTER anet_sandbox_transaction_key TYPE VARCHAR(16)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ALTER anet_sandbox_login_id TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE organization ALTER anet_sandbox_transaction_key TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE organization ALTER anet_login_id TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE organization ALTER anet_transaction_key TYPE VARCHAR(255)');
    }
}
