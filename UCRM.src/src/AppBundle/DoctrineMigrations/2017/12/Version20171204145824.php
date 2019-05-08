<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171204145824 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE last_seen_ticket_comment (id SERIAL NOT NULL, user_id INT NOT NULL, ticket_id INT NOT NULL, last_seen_ticket_comment_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C864DBCBA76ED395 ON last_seen_ticket_comment (user_id)');
        $this->addSql('CREATE INDEX IDX_C864DBCB700047D2 ON last_seen_ticket_comment (ticket_id)');
        $this->addSql('CREATE INDEX IDX_C864DBCBD3A805CC ON last_seen_ticket_comment (last_seen_ticket_comment_id)');
        $this->addSql('ALTER TABLE last_seen_ticket_comment ADD CONSTRAINT FK_C864DBCBA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE last_seen_ticket_comment ADD CONSTRAINT FK_C864DBCB700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE last_seen_ticket_comment ADD CONSTRAINT FK_C864DBCBD3A805CC FOREIGN KEY (last_seen_ticket_comment_id) REFERENCES ticket_comment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket ADD last_comment_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN ticket.last_comment_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('UPDATE ticket SET last_comment_at = \'1900-01-01\'');
        $this->addSql(
            'UPDATE ticket ti
                    SET last_comment_at = (
                      SELECT
                      max(created_at)
                      FROM ticket_activity
                      WHERE ticket_activity.dtype = \'ticketcomment\'
                      AND ticket_activity.ticket_id = ti.id
                )'
            );
        $this->addSql('ALTER TABLE ticket ALTER COLUMN last_comment_at SET NOT NULL');
        $this->addSql(
            'INSERT INTO last_seen_ticket_comment (user_id, ticket_id, last_seen_ticket_comment_id)
                    SELECT 
                      uadmins.user_id,
                      t.id,
                      (
                        SELECT ta.id
                        FROM ticket_activity ta
                        WHERE ta.dtype = \'ticketcomment\'
                        AND ta.ticket_id = t.id
                        ORDER BY ta.created_at DESC
                        LIMIT 1
                      ) AS max
                    FROM ticket t
                        CROSS JOIN (
                          SELECT u.user_id FROM "user" u
                          WHERE u.role IN (\'ROLE_SUPER_ADMIN\', \'ROLE_ADMIN\')
                        ) AS uadmins'
        );
        $this->addSql('ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE "user" SET created_at = COALESCE(last_login, \'1900-01-01\') WHERE role IN (\'ROLE_SUPER_ADMIN\', \'ROLE_ADMIN\', \'ROLE_WIZARD\')');
        $this->addSql('
            UPDATE "user" u SET created_at = c.registration_date
            FROM client c 
            WHERE u.user_id = c.user_id'
        );
        $this->addSql('UPDATE "user" SET created_at = \'1900-01-01\' WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE last_seen_ticket_comment');
        $this->addSql('ALTER TABLE organization ALTER quote_template_id SET DEFAULT 1');
        $this->addSql('ALTER TABLE ticket DROP last_comment_at');
        $this->addSql('ALTER TABLE "user" DROP COLUMN IF EXISTS created_at');
    }
}
