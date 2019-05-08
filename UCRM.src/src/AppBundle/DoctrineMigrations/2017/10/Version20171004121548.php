<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171004121548 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE payment_stripe_pending (id SERIAL NOT NULL, currency_id INT DEFAULT NULL, token_id INT NOT NULL, client_bank_account_id INT DEFAULT NULL, method INT NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, amount DOUBLE PRECISION NOT NULL, payment_details_id VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1E3AF14738248176 ON payment_stripe_pending (currency_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1E3AF14741DEE7B9 ON payment_stripe_pending (token_id)');
        $this->addSql('CREATE INDEX IDX_1E3AF1476FEDC4FF ON payment_stripe_pending (client_bank_account_id)');
        $this->addSql('COMMENT ON COLUMN payment_stripe_pending.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE payment_stripe_pending ADD CONSTRAINT FK_1E3AF14738248176 FOREIGN KEY (currency_id) REFERENCES currency (currency_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_stripe_pending ADD CONSTRAINT FK_1E3AF14741DEE7B9 FOREIGN KEY (token_id) REFERENCES payment_token (token_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_stripe_pending ADD CONSTRAINT FK_1E3AF1476FEDC4FF FOREIGN KEY (client_bank_account_id) REFERENCES client_bank_account (client_bank_account_id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE client_bank_account ADD stripe_bank_account_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE client_bank_account ADD stripe_bank_account_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE client_bank_account ADD stripe_bank_account_verified BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE client_bank_account ADD stripe_customer_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_token DROP CONSTRAINT FK_87E97892989F1FD');
        $this->addSql('DROP INDEX idx_87e97892989f1fd');
        $this->addSql('ALTER TABLE payment_token ADD CONSTRAINT FK_87E97892989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_87E97892989F1FD ON payment_token (invoice_id)');
        $this->addSql('ALTER TABLE organization ADD stripe_ach_enabled BOOLEAN DEFAULT \'false\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE payment_stripe_pending');
        $this->addSql('ALTER TABLE client_bank_account DROP stripe_bank_account_id');
        $this->addSql('ALTER TABLE client_bank_account DROP stripe_bank_account_token');
        $this->addSql('ALTER TABLE client_bank_account DROP stripe_bank_account_verified');
        $this->addSql('ALTER TABLE client_bank_account DROP stripe_customer_id');
        $this->addSql('ALTER TABLE payment_token DROP CONSTRAINT fk_87e97892989f1fd');
        $this->addSql('DROP INDEX UNIQ_87E97892989F1FD');
        $this->addSql('ALTER TABLE payment_token ADD CONSTRAINT fk_87e97892989f1fd FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_87e97892989f1fd ON payment_token (invoice_id)');
        $this->addSql('ALTER TABLE organization DROP stripe_ach_enabled');
    }
}
