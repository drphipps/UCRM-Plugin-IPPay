<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170118130614 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE client_contact (client_contact_id SERIAL NOT NULL, client_id INT NOT NULL, email citext DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, is_login BOOLEAN DEFAULT \'false\' NOT NULL, is_billing BOOLEAN DEFAULT \'false\' NOT NULL, is_contact BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(client_contact_id))');
        $this->addSql('CREATE INDEX IDX_1E5FA24519EB6921 ON client_contact (client_id)');
        $this->addSql('COMMENT ON COLUMN client_contact.email IS \'(DC2Type:citext)\'');
        $this->addSql('ALTER TABLE client_contact ADD CONSTRAINT FK_1E5FA24519EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Move contact details into client_contact table.
        $this->addSql('
            INSERT INTO "client_contact"
            ("client_id", "phone", "email", "is_login", "is_billing", "is_contact")
            SELECT c.client_id, c.phone1, u.email1, TRUE, TRUE, TRUE
            FROM "client" c
            JOIN "user" u ON c.user_id = u.user_id;
        ');
        $this->addSql('
            INSERT INTO "client_contact"
            ("client_id", "phone", "email", "is_login", "is_billing", "is_contact")
            SELECT c.client_id, c.phone2, u.email2, FALSE, FALSE, FALSE
            FROM "client" c
            JOIN "user" u ON c.user_id = u.user_id;
        ');
        $this->addSql(' 
            DELETE FROM "client_contact"
            WHERE phone IS NULL AND email IS NULL;
        ');

        // Remove duplicate rows, force citext and populate user names.
        $this->addSql('
            UPDATE "user"
            SET email1 = NULL
            FROM
            (
              SELECT ou.user_id, ou.email1, (ROW_NUMBER() OVER (PARTITION BY LOWER(ou.email1) ORDER BY ou.user_id)) AS row_number
              FROM "user" ou
              WHERE (SELECT COUNT(*) FROM "user" iu WHERE LOWER(ou.email1) = LOWER(iu.email1)) > 1
              ORDER BY ou.user_id
            ) dup
            WHERE "user".user_id = dup.user_id
            AND dup.row_number > 1
        ');
        $this->addSql('
            UPDATE "user"
            SET username = NULL
            FROM
            (
              SELECT ou.user_id, ou.username, (ROW_NUMBER() OVER (PARTITION BY LOWER(ou.username) ORDER BY ou.user_id)) AS row_number
              FROM "user" ou
              WHERE (SELECT COUNT(*) FROM "user" iu WHERE LOWER(ou.username) = LOWER(iu.username)) > 1
              ORDER BY ou.user_id
            ) dup
            WHERE "user".user_id = dup.user_id
            AND dup.row_number > 1
        ');
        $this->addSql('ALTER TABLE "user" ALTER "username" TYPE citext');
        $this->addSql('ALTER TABLE "user" ALTER "email1" TYPE citext');
        $this->addSql('
            UPDATE "user"
            SET email1 = NULL
            WHERE user_id IN (
              SELECT user_id
              FROM "user"
              WHERE email1 IN (
                SELECT username
                FROM "user"
              )
            )
        ');
        $this->addSql('
            UPDATE "user"
            SET username = email1
            WHERE 
              username IS NULL 
              AND email1 IS NOT NULL
        ');
        $this->addSql('UPDATE "user" SET email1 = NULL WHERE role = \'ROLE_CLIENT\'');

        $this->addSql('ALTER TABLE client DROP phone1');
        $this->addSql('ALTER TABLE client DROP phone2');
        $this->addSql('DROP INDEX uniq_8d93d649d453c8ee');
        $this->addSql('DROP INDEX uniq_8d93d6494d5a9954');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN email1 TO email');
        $this->addSql('ALTER TABLE "user" DROP email2');
        $this->addSql('COMMENT ON COLUMN "user".email IS \'(DC2Type:citext)\'');
        $this->addSql('COMMENT ON COLUMN "user".username IS \'(DC2Type:citext)\'');
        $this->addSql('ALTER TABLE invoice RENAME COLUMN client_phone1 TO client_phone');
        $this->addSql('ALTER TABLE invoice ALTER client_email DROP NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ADD phone1 VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD phone2 VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN email TO email1');
        $this->addSql('ALTER TABLE "user" ADD email2 citext DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".email1 IS \'This email can by used to log in as well as the username(DC2Type:citext)\'');
        $this->addSql('COMMENT ON COLUMN "user".email2 IS \'This email can by used to log in as well as the username(DC2Type:citext)\'');
        $this->addSql('COMMENT ON COLUMN "user".username IS \'used only for administrators(DC2Type:citext)\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d649d453c8ee ON "user" (email1)');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d6494d5a9954 ON "user" (email2)');

        // Move first contact back to email1 and phone1, we don't really care about email2 and phone2
        $this->addSql('
            UPDATE client
            SET phone1 = cc.phone
            FROM
            (
              SELECT cc.client_id, cc.phone, (ROW_NUMBER() OVER (PARTITION BY cc.client_id ORDER BY cc.client_id, cc.is_login DESC, cc.client_contact_id)) AS row_number
              FROM "client_contact" cc
              ORDER BY cc.client_id, cc.is_login DESC, cc.client_contact_id
            ) cc
            WHERE cc.row_number = 1 AND client.client_id = cc.client_id
        ');

        $this->addSql('
            UPDATE "user"
            SET email1 = cc.email, username = NULL
            FROM
            (
              SELECT c.user_id, cc.client_id, cc.email, (ROW_NUMBER() OVER (PARTITION BY cc.client_id ORDER BY cc.client_id, cc.is_login DESC, cc.client_contact_id)) AS row_number
              FROM "client_contact" cc
              JOIN "client" c ON c.client_id = cc.client_id
              ORDER BY cc.client_id, cc.is_login DESC, cc.client_contact_id
            ) cc
            WHERE cc.row_number = 1
            AND "user".role = \'ROLE_CLIENT\'
            AND "user".user_id = cc.user_id
        ');

        $this->addSql('DROP TABLE client_contact');
        $this->addSql('ALTER TABLE invoice ADD client_phone2 VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ALTER client_email SET NOT NULL');
        $this->addSql('ALTER TABLE invoice RENAME COLUMN client_phone TO client_phone1');
    }
}
