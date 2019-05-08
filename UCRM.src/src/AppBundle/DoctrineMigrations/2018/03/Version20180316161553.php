<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180316161553 extends AbstractMigration
{
    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('CREATE TABLE invoice_attribute (id SERIAL NOT NULL, invoice_id INT NOT NULL, attribute_id INT NOT NULL, value TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9FD51E672989F1FD ON invoice_attribute (invoice_id)');
        $this->addSql('CREATE INDEX IDX_9FD51E67B6E62EFA ON invoice_attribute (attribute_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9FD51E672989F1FDB6E62EFA ON invoice_attribute (invoice_id, attribute_id)');
        $this->addSql('ALTER TABLE invoice_attribute ADD CONSTRAINT FK_9FD51E672989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice_attribute ADD CONSTRAINT FK_9FD51E67B6E62EFA FOREIGN KEY (attribute_id) REFERENCES custom_attribute (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE custom_attribute ADD attribute_type VARCHAR(255) NULL');
        $this->addSql('UPDATE custom_attribute SET attribute_type = \'client\'');
        $this->addSql('ALTER TABLE custom_attribute ALTER attribute_type SET NOT NULL');
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('ALTER TABLE custom_attribute DROP attribute_type');
        $this->addSql('DROP TABLE invoice_attribute');
    }
}
