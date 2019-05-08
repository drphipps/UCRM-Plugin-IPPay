<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160502153618 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET position = 0 WHERE option_id = 11');
        $this->addSql('UPDATE option SET position = 1, choice_type_options = \'{"choices":{"smtp":"SMTP","gmail":"Gmail"},"choice_translation_domain":false}\', description = \'Choose Gmail as a shortcut for host: "smtp.gmail.com", authentication mode: "LOGIN" and encryption: "SSL"\' WHERE option_id = 9');
        $this->addSql('UPDATE option SET position = 2 WHERE option_id = 12');
        $this->addSql('UPDATE option SET position = 3, type = \'password\' WHERE option_id = 13');
        $this->addSql('UPDATE option SET position = 4 WHERE option_id = 10');
        $this->addSql('INSERT INTO option (option_id, position, code, name, description, type, value, category_id, value_txt, choice_type_options) VALUES (22, 5, \'MAILER_PORT\', \'Port\', NULL, \'string\', NULL, \'3\', NULL, NULL)');
        $this->addSql('INSERT INTO option (option_id, position, code, name, description, type, value, category_id, value_txt, choice_type_options) VALUES (23, 6, \'MAILER_ENCRYPTION\', \'Encryption\', NULL, \'choice\', NULL, \'3\', NULL, \'{"choices":{"":"-","ssl":"SSL","tls":"TLS"},"choice_translation_domain":false}\')');
        $this->addSql('INSERT INTO option (option_id, position, code, name, description, type, value, category_id, value_txt, choice_type_options) VALUES (24, 7, \'MAILER_AUTH_MODE\', \'Use authentication\', NULL, \'choice\', NULL, \'3\', NULL, \'{"choices":{"":"No authentication","plain":"Yes (PLAIN)","login":"Yes (LOGIN)","cram-md5":"Yes (CRAM-MD5)"}}\')');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET position = 4 WHERE option_id = 11');
        $this->addSql('UPDATE option SET position = 3, choice_type_options = \'{"choices":{"smtp":"smtp","gmail":"gmail","mail":"mail","sendmail":"sendmail"},"choice_translation_domain":false}\' WHERE option_id = 9');
        $this->addSql('UPDATE option SET position = 1 WHERE option_id = 12');
        $this->addSql('UPDATE option SET position = 2, type = \'string\' WHERE option_id = 13');
        $this->addSql('UPDATE option SET position = 0 WHERE option_id = 10');
        $this->addSql('DELETE FROM option WHERE option_id = 22');
        $this->addSql('DELETE FROM option WHERE option_id = 23');
        $this->addSql('DELETE FROM option WHERE option_id = 24');
    }
}
