<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161014111750 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE timezone (timezone_id SERIAL NOT NULL, name VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, PRIMARY KEY(timezone_id))');

        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (1, \'Pacific/Midway\', \'(UTC-11:00) Midway Island, American Samoa\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (2, \'America/Adak\', \'(UTC-10:00) Aleutian Islands\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (3, \'Pacific/Honolulu\', \'(UTC-10:00) Hawaii\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (4, \'Pacific/Marquesas\', \'(UTC-09:30) Marquesas Islands\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (5, \'America/Anchorage\', \'(UTC-09:00) Alaska\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (6, \'America/Tijuana\', \'(UTC-08:00) Baja California\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (7, \'America/Los_Angeles\', \'(UTC-08:00) Pacific Time (US and Canada)\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (8, \'America/Phoenix\', \'(UTC-07:00) Arizona\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (9, \'America/Chihuahua\', \'(UTC-07:00) Chihuahua, La Paz, Mazatlan\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (10, \'America/Denver\', \'(UTC-07:00) Mountain Time (US and Canada)\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (11, \'America/Belize\', \'(UTC-06:00) Central America\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (12, \'America/Chicago\', \'(UTC-06:00) Central Time (US and Canada)\t\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (13, \'Pacific/Easter\', \'(UTC-06:00) Easter Island\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (14, \'America/Mexico_City\', \'(UTC-06:00) Guadalajara, Mexico City, Monterrey\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (15, \'America/Regina\', \'(UTC-06:00) Saskatchewan\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (16, \'America/Bogota\', \'(UTC-05:00) Bogota, Lima, Quito\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (17, \'America/Cancun\', \'(UTC-05:00) Chetumal\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (18, \'America/New_York\', \'(UTC-05:00) Eastern Time (US and Canada)\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (19, \'America/Port-au-Prince\', \'(UTC-05:00) Haiti\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (20, \'America/Havana\', \'(UTC-05:00) Havana\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (21, \'America/Indiana/Indianapolis\', \'(UTC-05:00) Indiana (East)\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (22, \'America/Asuncion\', \'(UTC-04:00) Asuncion\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (23, \'America/Halifax\', \'(UTC-04:00) Atlantic Time (Canada)\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (24, \'America/Caracas\', \'(UTC-04:00) Caracas\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (25, \'America/Cuiaba\', \'(UTC-04:00) Cuiaba\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (26, \'America/Manaus\', \'(UTC-04:00) Georgetown, La Paz, Manaus, San Juan\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (27, \'America/Santiago\', \'(UTC-04:00) Santiago\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (28, \'America/Grand_Turk\', \'(UTC-04:00) Turks and Caicos\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (29, \'America/St_Johns\', \'(UTC-03:30) Newfoundland\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (30, \'America/Fortaleza\', \'(UTC-03:00) Araguaina\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (31, \'America/Sao_Paulo\', \'(UTC-03:00) Brasilia\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (32, \'America/Cayenne\', \'(UTC-03:00) Cayenne, Fortaleza\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (33, \'America/Buenos_Aires\', \'(UTC-03:00) City of Buenos Aires\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (34, \'America/Godthab\', \'(UTC-03:00) Greenland\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (35, \'America/Montevideo\', \'(UTC-03:00) Montevideo\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (36, \'America/Miquelon\', \'(UTC-03:00) Saint Pierre and Miquelon\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (37, \'America/Bahia\', \'(UTC-03:00) Salvador\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (38, \'Atlantic/Azores\', \'(UTC-01:00) Azores\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (39, \'Atlantic/Cape_Verde\', \'(UTC-01:00) Cabo Verde Islands\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (40, \'Africa/Casablanca\', \'(UTC) Casablanca\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (41, \'Europe/London\', \'(UTC) Dublin, Edinburgh, Lisbon, London\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (42, \'Etc/UTC\', \'(UTC) Coordinated Universal Time\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (43, \'Africa/Monrovia\', \'(UTC) Monrovia, Reykjavik\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (44, \'Europe/Amsterdam\', \'(UTC+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (45, \'Europe/Belgrade\', \'(UTC+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (46, \'Europe/Brussels\', \'(UTC+01:00) Brussels, Copenhagen, Madrid, Paris\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (47, \'Europe/Warsaw\', \'(UTC+01:00) Sarajevo, Skopje, Warsaw, Zagreb\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (48, \'Africa/Algiers\', \'(UTC+01:00) West Central Africa\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (49, \'Africa/Windhoek\', \'(UTC+01:00) Windhoek\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (50, \'Asia/Amman\', \'(UTC+02:00) Amman\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (51, \'Europe/Athens\', \'(UTC+02:00) Athens, Bucharest\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (52, \'Asia/Beirut\', \'(UTC+02:00) Beirut\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (53, \'Africa/Cairo\', \'(UTC+02:00) Cairo\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (54, \'Asia/Damascus\', \'(UTC+02:00) Damascus\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (55, \'Asia/Gaza\', \'(UTC+02:00) Gaza, Hebron\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (56, \'Africa/Harare\', \'(UTC+02:00) Harare, Pretoria\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (57, \'Europe/Helsinki\', \'(UTC+02:00) Helsinki, Kiev, Riga, Sofia, Tallinn, Vilnius\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (58, \'Asia/Istanbul\', \'(UTC+02:00) Istanbul\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (59, \'Asia/Jerusalem\', \'(UTC+02:00) Jerusalem\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (60, \'Europe/Kaliningrad\', \'(UTC+02:00) Kaliningrad\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (61, \'Africa/Tripoli\', \'(UTC+02:00) Tripoli\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (62, \'Asia/Baghdad\', \'(UTC+03:00) Baghdad\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (63, \'Asia/Kuwait\', \'(UTC+03:00) Kuwait, Riyadh\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (64, \'Europe/Minsk\', \'(UTC+03:00) Minsk\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (65, \'Europe/Moscow\', \'(UTC+03:00) Moscow, St. Petersburg, Volgograd\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (66, \'Africa/Nairobi\', \'(UTC+03:00) Nairobi\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (67, \'Asia/Tehran\', \'(UTC+03:30) Tehran\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (68, \'Asia/Muscat\', \'(UTC+04:00) Abu Dhabi, Muscat\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (69, \'Europe/Astrakhan\', \'(UTC+04:00) Astrakhan, Ulyanovsk\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (70, \'Asia/Baku\', \'(UTC+04:00) Baku\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (71, \'Europe/Samara\', \'(UTC+04:00) Izhevsk, Samara\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (72, \'Indian/Mauritius\', \'(UTC+04:00) Port Louis\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (73, \'Asia/Tbilisi\', \'(UTC+04:00) Tbilisi\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (74, \'Asia/Yerevan\', \'(UTC+04:00) Yerevan\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (75, \'Asia/Kabul\', \'(UTC+04:30) Kabul\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (76, \'Asia/Tashkent\', \'(UTC+05:00) Tashkent, Ashgabat\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (77, \'Asia/Yekaterinburg\', \'(UTC+05:00) Ekaterinburg\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (78, \'Asia/Karachi\', \'(UTC+05:00) Islamabad, Karachi\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (79, \'Asia/Kolkata\', \'(UTC+05:30) Chennai, Kolkata, Mumbai, New Delhi\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (80, \'Asia/Colombo\', \'(UTC+05:30) Sri Jayawardenepura\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (81, \'Asia/Katmandu\', \'(UTC+05:45) Kathmandu\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (82, \'Asia/Almaty\', \'(UTC+06:00) Astana\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (83, \'Asia/Dhaka\', \'(UTC+06:00) Dhaka\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (84, \'Asia/Novosibirsk\', \'(UTC+06:00) Novosibirsk\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (85, \'Asia/Rangoon\', \'(UTC+06:30) Yangon (Rangoon)\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (86, \'Asia/Bangkok\', \'(UTC+07:00) Bangkok, Hanoi, Jakarta\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (87, \'Asia/Barnaul\', \'(UTC+07:00) Barnaul, Gorno-Altaysk\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (88, \'Asia/Hovd\', \'(UTC+07:00) Hovd\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (89, \'Asia/Krasnoyarsk\', \'(UTC+07:00) Krasnoyarsk\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (90, \'Asia/Tomsk\', \'(UTC+07:00) Tomsk\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (91, \'Asia/Chongqing\', \'(UTC+08:00) Beijing, Chongqing, Hong Kong SAR, Urumqi\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (92, \'Asia/Irkutsk\', \'(UTC+08:00) Irkutsk\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (93, \'Asia/Kuala_Lumpur\', \'(UTC+08:00) Kuala Lumpur, Singapore\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (94, \'Australia/Perth\', \'(UTC+08:00) Perth\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (95, \'Asia/Taipei\', \'(UTC+08:00) Taipei\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (96, \'Asia/Ulaanbaatar\', \'(UTC+08:00) Ulaanbaatar\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (97, \'Asia/Pyongyang\', \'(UTC+08:30) Pyongyang\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (98, \'Australia/Eucla\', \'(UTC+08:45) Eucla\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (99, \'Asia/Chita\', \'(UTC+09:00) Chita\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (100, \'Asia/Tokyo\', \'(UTC+09:00) Osaka, Sapporo, Tokyo\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (101, \'Asia/Seoul\', \'(UTC+09:00) Seoul\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (102, \'Asia/Yakutsk\', \'(UTC+09:00) Yakutsk\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (103, \'Australia/Adelaide\', \'(UTC+09:30) Adelaide\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (104, \'Australia/Darwin\', \'(UTC+09:30) Darwin\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (105, \'Australia/Brisbane\', \'(UTC+10:00) Brisbane\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (106, \'Australia/Canberra\', \'(UTC+10:00) Canberra, Melbourne, Sydney\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (107, \'Pacific/Guam\', \'(UTC+10:00) Guam, Port Moresby\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (108, \'Australia/Hobart\', \'(UTC+10:00) Hobart\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (109, \'Asia/Vladivostok\', \'(UTC+10:00) Vladivostok\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (110, \'Australia/Lord_Howe\', \'(UTC+10:30) Lord Howe Island\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (111, \'Pacific/Bougainville\', \'(UTC+11:00) Bougainville Island\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (112, \'Asia/Srednekolymsk\', \'(UTC+11:00) Chokirdakh\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (113, \'Asia/Magadan\', \'(UTC+11:00) Magadan\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (114, \'Pacific/Norfolk\', \'(UTC+11:00) Norfolk Island\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (115, \'Asia/Sakhalin\', \'(UTC+11:00) Sakhalin\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (116, \'Pacific/Guadalcanal\', \'(UTC+11:00) Solomon Islands, New Caledonia\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (117, \'Asia/Anadyr\', \'(UTC+12:00) Anadyr, Petropavlovsk-Kamchatsky\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (118, \'Pacific/Auckland\', \'(UTC+12:00) Auckland, Wellington\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (119, \'Pacific/Fiji\', \'(UTC+12:00) Fiji Islands\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (120, \'Pacific/Chatham\', \'(UTC+12:45) Chatham Islands\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (121, \'Pacific/Tongatapu\', \'(UTC+13:00) Nuku\'\'alofa\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (122, \'Pacific/Apia\', \'(UTC+13:00) Samoa\')');
        $this->addSql('INSERT INTO timezone (timezone_id, name, label) VALUES (123, \'Pacific/Kiritimati\', \'(UTC+14:00) Kiritimati Island\')');

        $this->addSql('ALTER TABLE option DROP CONSTRAINT fk_5a8600b012469de2');
        $this->addSql('DROP INDEX idx_5a8600b012469de2');
        $this->addSql('ALTER TABLE option DROP category_id');
        $this->addSql('ALTER TABLE option DROP "position"');
        $this->addSql('ALTER TABLE option DROP name');
        $this->addSql('ALTER TABLE option DROP description');
        $this->addSql('ALTER TABLE option DROP type');
        $this->addSql('ALTER TABLE option DROP choice_type_options');
        $this->addSql('ALTER TABLE option DROP validation_rules');
        $this->addSql('ALTER TABLE option DROP help');
        $this->addSql('ALTER TABLE option ALTER value TYPE TEXT');
        $this->addSql('ALTER TABLE option ALTER value DROP DEFAULT');
        $this->addSql('UPDATE option SET value = value_txt WHERE code = \'MAILER_PASSWORD\' AND value_txt IS NOT NULL');
        $this->addSql('ALTER TABLE option DROP value_txt');

        $this->addSql('DROP SEQUENCE setting_category_category_id_seq CASCADE');
        $this->addSql('DROP TABLE setting_category');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE timezone');

        $this->addSql('ALTER TABLE option ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE option ADD "position" INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE option ADD name VARCHAR(90) NOT NULL');
        $this->addSql('ALTER TABLE option ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE option ADD type VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE option ADD value_txt TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE option ADD choice_type_options JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE option ADD validation_rules JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE option ADD help VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE option ALTER value TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE option ALTER value DROP DEFAULT');
        $this->addSql('ALTER TABLE option ADD CONSTRAINT fk_5a8600b012469de2 FOREIGN KEY (category_id) REFERENCES setting_category (category_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_5a8600b012469de2 ON option (category_id)');

        $this->addSql('CREATE TABLE setting_category (category_id SERIAL NOT NULL, name VARCHAR(90) NOT NULL, PRIMARY KEY(category_id))');
    }
}
