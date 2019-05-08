<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180205081654 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log ALTER sender TYPE TEXT');
        $this->addSql('ALTER TABLE email_log ALTER recipient TYPE TEXT');
        $this->addSql('ALTER TABLE email_log ALTER subject TYPE TEXT');
        $this->addSql('ALTER TABLE email_log ALTER original_recipient TYPE TEXT');
        $this->addSql('ALTER TABLE email_log ALTER address_from TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log ALTER sender TYPE VARCHAR(320)');
        $this->addSql('ALTER TABLE email_log ALTER recipient TYPE VARCHAR(320)');
        $this->addSql('ALTER TABLE email_log ALTER original_recipient TYPE VARCHAR(320)');
        $this->addSql('ALTER TABLE email_log ALTER subject TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE email_log ALTER address_from TYPE VARCHAR(320)');
    }
}
