<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170911104649 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE user_group_permission DROP CONSTRAINT FK_4A91B1C5FE54D947');
        $this->addSql('ALTER TABLE user_group_permission ADD CONSTRAINT FK_4A91B1C5FE54D947 FOREIGN KEY (group_id) REFERENCES user_group (group_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_group_special_permission DROP CONSTRAINT FK_374D3E79FE54D947');
        $this->addSql('ALTER TABLE user_group_special_permission ADD CONSTRAINT FK_374D3E79FE54D947 FOREIGN KEY (group_id) REFERENCES user_group (group_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE user_group_permission DROP CONSTRAINT fk_4a91b1c5fe54d947');
        $this->addSql('ALTER TABLE user_group_permission ADD CONSTRAINT fk_4a91b1c5fe54d947 FOREIGN KEY (group_id) REFERENCES user_group (group_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_group_special_permission DROP CONSTRAINT fk_374d3e79fe54d947');
        $this->addSql('ALTER TABLE user_group_special_permission ADD CONSTRAINT fk_374d3e79fe54d947 FOREIGN KEY (group_id) REFERENCES user_group (group_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
