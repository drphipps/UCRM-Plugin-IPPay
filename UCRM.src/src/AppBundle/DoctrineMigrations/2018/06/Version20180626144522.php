<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180626144522 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('
          INSERT INTO general
              (code, value)
          VALUES
              (\'feedback_email\', \'\')
        ');

        $this->addSql('
          INSERT INTO general
              (code, value)
          VALUES
              (\'feedback_email_sent\', \'0\')
        ');

        $this->addSql(
            '
              INSERT INTO general
                (code, value)
              VALUES
                (\'ucrm_installed_date\', ?)
            ',
            [
                (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTime::ATOM),
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'DELETE FROM general WHERE code IN (?, ?, ?)',
            [
                'feedback_email',
                'feedback_email_sent',
                'ucrm_installed_date',
            ]
        );
    }
}
