<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170810075330 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE surcharge ADD tax_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE surcharge ADD CONSTRAINT FK_54C9B0DFB2A824D8 FOREIGN KEY (tax_id) REFERENCES tax (tax_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_54C9B0DFB2A824D8 ON surcharge (tax_id)');
        $this->addSql('ALTER TABLE tariff ADD tax_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff ADD CONSTRAINT FK_9465207DB2A824D8 FOREIGN KEY (tax_id) REFERENCES tax (tax_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_9465207DB2A824D8 ON tariff (tax_id)');
        $this->addSql('ALTER TABLE product ADD tax_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB2A824D8 FOREIGN KEY (tax_id) REFERENCES tax (tax_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D34A04ADB2A824D8 ON product (tax_id)');

        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'LATE_FEE_TAX_ID',
                '',
            ]
        );
        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'SETUP_FEE_TAX_ID',
                '',
            ]
        );
        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'EARLY_TERMINATION_FEE_TAX_ID',
                '',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE product DROP CONSTRAINT FK_D34A04ADB2A824D8');
        $this->addSql('DROP INDEX IDX_D34A04ADB2A824D8');
        $this->addSql('ALTER TABLE product DROP tax_id');
        $this->addSql('ALTER TABLE surcharge DROP CONSTRAINT FK_54C9B0DFB2A824D8');
        $this->addSql('DROP INDEX IDX_54C9B0DFB2A824D8');
        $this->addSql('ALTER TABLE surcharge DROP tax_id');
        $this->addSql('ALTER TABLE tariff DROP CONSTRAINT FK_9465207DB2A824D8');
        $this->addSql('DROP INDEX IDX_9465207DB2A824D8');
        $this->addSql('ALTER TABLE tariff DROP tax_id');

        $this->addSql(
            'DELETE FROM option WHERE code IN (?, ?, ?)',
            [
                'LATE_FEE_TAX_ID',
                'SETUP_FEE_TAX_ID',
                'EARLY_TERMINATION_FEE_TAX_ID',
            ]
        );
    }
}
