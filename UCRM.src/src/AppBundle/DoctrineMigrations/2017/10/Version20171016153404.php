<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171016153404 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_contact ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE TABLE client_contact_contact_type (client_contact_client_contact_id INT NOT NULL, contact_type_id INT NOT NULL, PRIMARY KEY(client_contact_client_contact_id, contact_type_id))');
        $this->addSql('CREATE INDEX IDX_183427F94A96FEDD ON client_contact_contact_type (client_contact_client_contact_id)');
        $this->addSql('CREATE INDEX IDX_183427F95F63AD12 ON client_contact_contact_type (contact_type_id)');
        $this->addSql('CREATE TABLE contact_type (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A421D5D65E237E06 ON contact_type (name)');
        $this->addSql('ALTER TABLE client_contact_contact_type ADD CONSTRAINT FK_183427F94A96FEDD FOREIGN KEY (client_contact_client_contact_id) REFERENCES client_contact (client_contact_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_contact_contact_type ADD CONSTRAINT FK_183427F95F63AD12 FOREIGN KEY (contact_type_id) REFERENCES contact_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO contact_type (id, name) VALUES (1, \'Billing\')');
        $this->addSql('INSERT INTO contact_type (id, name) VALUES (2, \'General\')');
        $this->addSql('ALTER SEQUENCE contact_type_id_seq RESTART WITH 1000');

        $this->addSql('INSERT INTO client_contact_contact_type (client_contact_client_contact_id, contact_type_id) SELECT client_contact_id, 1 FROM client_contact WHERE is_billing');
        $this->addSql('INSERT INTO client_contact_contact_type (client_contact_client_contact_id, contact_type_id) SELECT client_contact_id, 2 FROM client_contact WHERE is_contact');

        $this->addSql('ALTER TABLE client_contact DROP is_billing');
        $this->addSql('ALTER TABLE client_contact DROP is_contact');

        $this->addSql('INSERT INTO user_group_permission (group_id, module_name, permission) VALUES (1, \'AppBundle\Controller\ContactTypeController\', \'edit\')');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_contact ADD is_billing BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE client_contact ADD is_contact BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE client_contact DROP name');

        $this->addSql('ALTER TABLE client_contact_contact_type DROP CONSTRAINT FK_183427F95F63AD12');

        $this->addSql('UPDATE client_contact SET is_billing=FALSE');
        $this->addSql('UPDATE client_contact cc SET is_billing=TRUE FROM client_contact_contact_type ccct WHERE cc.client_contact_id=ccct.client_contact_client_contact_id AND ccct.contact_type_id=1');
        $this->addSql('UPDATE client_contact SET is_contact=FALSE');
        $this->addSql('UPDATE client_contact cc SET is_contact=TRUE FROM client_contact_contact_type ccct WHERE cc.client_contact_id=ccct.client_contact_client_contact_id AND ccct.contact_type_id=2');

        $this->addSql('DROP TABLE client_contact_contact_type');
        $this->addSql('DROP TABLE contact_type');

        $this->addSql('DELETE FROM user_group_permission WHERE module_name = \'AppBundle\Controller\ContactTypeController\'');
    }
}
