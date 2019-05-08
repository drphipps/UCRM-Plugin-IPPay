<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160630070627 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_cover ADD refund_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_cover ADD CONSTRAINT FK_DA41B077189801D5 FOREIGN KEY (refund_id) REFERENCES refund (refund_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_DA41B077189801D5 ON payment_cover (refund_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_cover DROP CONSTRAINT FK_DA41B077189801D5');
        $this->addSql('DROP INDEX IDX_DA41B077189801D5');
        $this->addSql('ALTER TABLE payment_cover DROP refund_id');
    }
}
