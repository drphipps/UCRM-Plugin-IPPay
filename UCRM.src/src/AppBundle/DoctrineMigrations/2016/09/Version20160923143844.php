<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160923143844 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE option SET help = \'google-api\' WHERE code = \'GOOGLE_API_KEY\'');
        $this->addSql('UPDATE option SET help = \'invoicing-type\' WHERE code = \'INVOICING_PERIOD_TYPE\'');
        $this->addSql('UPDATE option SET help = \'invoicing-prorated\' WHERE code = \'BILLING_CYCLE_TYPE\'');
        $this->addSql('UPDATE option SET help = \'outage-monitoring\' WHERE code = \'NOTIFICATION_PING_USER\'');
        $this->addSql('UPDATE option SET help = \'network-suspend\' WHERE code = \'SUSPEND_ENABLED\'');
    }

    public function down(Schema $schema)
    {
        $this->addSql('UPDATE option SET help = NULL WHERE code = \'GOOGLE_API_KEY\'');
        $this->addSql('UPDATE option SET help = NULL WHERE code = \'INVOICING_PERIOD_TYPE\'');
        $this->addSql('UPDATE option SET help = NULL WHERE code = \'BILLING_CYCLE_TYPE\'');
        $this->addSql('UPDATE option SET help = NULL WHERE code = \'NOTIFICATION_PING_USER\'');
        $this->addSql('UPDATE option SET help = NULL WHERE code = \'SUSPEND_ENABLED\'');
    }
}
