<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160622135050 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE refund_refund_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE refund (refund_id INT NOT NULL, client_id INT DEFAULT NULL, currency_id INT DEFAULT NULL, method INT NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, amount DOUBLE PRECISION NOT NULL, note TEXT DEFAULT NULL, PRIMARY KEY(refund_id))');
        $this->addSql('CREATE INDEX IDX_5B2C145819EB6921 ON refund (client_id)');
        $this->addSql('CREATE INDEX IDX_5B2C145838248176 ON refund (currency_id)');
        $this->addSql('ALTER TABLE refund ADD CONSTRAINT FK_5B2C145819EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE refund ADD CONSTRAINT FK_5B2C145838248176 FOREIGN KEY (currency_id) REFERENCES currency (currency_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (30, 1, \'AppBundle\Controller\RefundController\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE refund_refund_id_seq CASCADE');
        $this->addSql('DROP TABLE refund');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id=30');
    }
}
