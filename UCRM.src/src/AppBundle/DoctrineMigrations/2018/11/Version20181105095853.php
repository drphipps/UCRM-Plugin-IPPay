<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181105095853 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_import_item DROP CONSTRAINT fk_f37ceea5741c8b98');
        $this->addSql('DROP INDEX uniq_f37ceea5741c8b98');
        $this->addSql('ALTER TABLE client_import_item DROP validation_errors_id');
        $this->addSql('ALTER TABLE service_import_item DROP CONSTRAINT fk_3eaded32741c8b98');
        $this->addSql('DROP INDEX uniq_3eaded32741c8b98');
        $this->addSql('ALTER TABLE service_import_item DROP validation_errors_id');
        $this->addSql('ALTER TABLE client_import_item_validation_errors ADD client_import_item_id UUID NOT NULL');
        $this->addSql('ALTER TABLE client_import_item_validation_errors ADD CONSTRAINT FK_CADACCBF20BCB93D FOREIGN KEY (client_import_item_id) REFERENCES client_import_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CADACCBF20BCB93D ON client_import_item_validation_errors (client_import_item_id)');
        $this->addSql('ALTER TABLE service_import_item_validation_errors ADD service_import_item_id UUID NOT NULL');
        $this->addSql('ALTER TABLE service_import_item_validation_errors ADD CONSTRAINT FK_BA186A0D219A604 FOREIGN KEY (service_import_item_id) REFERENCES service_import_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BA186A0D219A604 ON service_import_item_validation_errors (service_import_item_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_import_item ADD validation_errors_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE service_import_item ADD CONSTRAINT fk_3eaded32741c8b98 FOREIGN KEY (validation_errors_id) REFERENCES service_import_item_validation_errors (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_3eaded32741c8b98 ON service_import_item (validation_errors_id)');
        $this->addSql('ALTER TABLE service_import_item_validation_errors DROP CONSTRAINT FK_BA186A0D219A604');
        $this->addSql('DROP INDEX UNIQ_BA186A0D219A604');
        $this->addSql('ALTER TABLE service_import_item_validation_errors DROP service_import_item_id');
        $this->addSql('ALTER TABLE client_import_item_validation_errors DROP CONSTRAINT FK_CADACCBF20BCB93D');
        $this->addSql('DROP INDEX UNIQ_CADACCBF20BCB93D');
        $this->addSql('ALTER TABLE client_import_item_validation_errors DROP client_import_item_id');
        $this->addSql('ALTER TABLE client_import_item ADD validation_errors_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE client_import_item ADD CONSTRAINT fk_f37ceea5741c8b98 FOREIGN KEY (validation_errors_id) REFERENCES client_import_item_validation_errors (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_f37ceea5741c8b98 ON client_import_item (validation_errors_id)');
    }
}
