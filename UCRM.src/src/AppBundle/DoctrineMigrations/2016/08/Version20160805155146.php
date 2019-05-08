<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160805155146 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_anet DROP CONSTRAINT FK_8EF614E319EB6921');
        $this->addSql('ALTER TABLE payment_anet ADD CONSTRAINT FK_8EF614E319EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_paypal DROP CONSTRAINT FK_41AE99DA19EB6921');
        $this->addSql('ALTER TABLE payment_paypal ADD CONSTRAINT FK_41AE99DA19EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_stripe DROP CONSTRAINT FK_81C977CC19EB6921');
        $this->addSql('ALTER TABLE payment_stripe ADD CONSTRAINT FK_81C977CC19EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_anet DROP CONSTRAINT fk_8ef614e319eb6921');
        $this->addSql('ALTER TABLE payment_anet ADD CONSTRAINT fk_8ef614e319eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_paypal DROP CONSTRAINT fk_41ae99da19eb6921');
        $this->addSql('ALTER TABLE payment_paypal ADD CONSTRAINT fk_41ae99da19eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_stripe DROP CONSTRAINT fk_81c977cc19eb6921');
        $this->addSql('ALTER TABLE payment_stripe ADD CONSTRAINT fk_81c977cc19eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
