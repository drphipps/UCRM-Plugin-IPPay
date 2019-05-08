<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181004130222 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service DROP CONSTRAINT FK_E19D9AD219EB6921');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD219EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_9065174419EB6921');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_9065174419EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840D19EB6921');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D19EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_plan DROP CONSTRAINT FK_FCD9CC0919EB6921');
        $this->addSql('ALTER TABLE payment_plan ADD CONSTRAINT FK_FCD9CC0919EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE refund DROP CONSTRAINT FK_5B2C145819EB6921');
        $this->addSql('ALTER TABLE refund ADD CONSTRAINT FK_5B2C145819EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote DROP CONSTRAINT FK_6B71CBF419EB6921');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF419EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document DROP CONSTRAINT FK_D8698A7619EB6921');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A7619EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_bank_account DROP CONSTRAINT FK_E2A89C0B19EB6921');
        $this->addSql('ALTER TABLE client_bank_account ADD CONSTRAINT FK_E2A89C0B19EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE credit DROP CONSTRAINT FK_1CC16EFE19EB6921');
        $this->addSql('ALTER TABLE credit ADD CONSTRAINT FK_1CC16EFE19EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE document DROP CONSTRAINT fk_d8698a7619eb6921');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT fk_d8698a7619eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE credit DROP CONSTRAINT fk_1cc16efe19eb6921');
        $this->addSql('ALTER TABLE credit ADD CONSTRAINT fk_1cc16efe19eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_bank_account DROP CONSTRAINT fk_e2a89c0b19eb6921');
        $this->addSql('ALTER TABLE client_bank_account ADD CONSTRAINT fk_e2a89c0b19eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT fk_6d28840d19eb6921');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT fk_6d28840d19eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT fk_9065174419eb6921');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT fk_9065174419eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service DROP CONSTRAINT fk_e19d9ad219eb6921');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT fk_e19d9ad219eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_plan DROP CONSTRAINT fk_fcd9cc0919eb6921');
        $this->addSql('ALTER TABLE payment_plan ADD CONSTRAINT fk_fcd9cc0919eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE refund DROP CONSTRAINT fk_5b2c145819eb6921');
        $this->addSql('ALTER TABLE refund ADD CONSTRAINT fk_5b2c145819eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote DROP CONSTRAINT fk_6b71cbf419eb6921');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT fk_6b71cbf419eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
