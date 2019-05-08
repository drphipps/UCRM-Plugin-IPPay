<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180208124021 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        // Show HP onboarding for new installations only.
        $this->addSql('
          INSERT INTO general 
              (code, value)
          SELECT 
              \'onboarding_homepage_finished\',
              CASE WHEN EXISTS(SELECT client_id FROM client WHERE deleted_at IS NULL LIMIT 1)
                THEN \'1\'
                ELSE \'0\'
              END
        ');

        $this->addSql('
          INSERT INTO general
              (code, value)
          VALUES
              (\'onboarding_homepage_billing\', 0),
              (\'onboarding_homepage_system\', 0),
              (\'onboarding_homepage_mailer\', 0),
              (\'onboarding_homepage_mailer_via_wizard\', 0)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'DELETE FROM general WHERE code IN (?, ?, ?, ?, ?)',
            [
                'onboarding_homepage_finished',
                'onboarding_homepage_billing',
                'onboarding_homepage_system',
                'onboarding_homepage_mailer',
                'onboarding_homepage_mailer_via_wizard',
            ]
        );
    }
}
