<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171016095834 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (25, \'Service stopped\', \'%s\', 25);',
                '<p>Dear %CLIENT_NAME%,<br>your internet service %SERVICE_TARIFF% has been suspended.<br>Reason: %SERVICE_STOP_REASON%</p>'
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM notification_template WHERE template_id = 25');
    }
}
