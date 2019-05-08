<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160720153230 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE "option" SET "position" = "position" + \'1\' WHERE "category_id" = \'2\';');
        $this->addSql('UPDATE "option" SET "category_id" = \'2\', "position" = \'0\', "name" = \'Hour when recurring invoices will be generated\' WHERE "option_id" = \'14\';');

        $this->addSql(
            'INSERT INTO "option" ("option_id", "category_id", "position", "code", "name", "description", "type", "value", "value_txt", "choice_type_options", "validation_rules")
            VALUES (\'33\', \'1\', \'5\', \'APP_TIMEZONE\', \'Timezone\', NULL, \'choice\', \'Etc/UTC\', NULL, \'{"choices":{"Pacific\/Midway":"(UTC-11:00) Midway Island, American Samoa","America\/Adak":"(UTC-10:00) Aleutian Islands","Pacific\/Honolulu":"(UTC-10:00) Hawaii","Pacific\/Marquesas":"(UTC-09:30) Marquesas Islands","America\/Anchorage":"(UTC-09:00) Alaska","America\/Tijuana":"(UTC-08:00) Baja California","America\/Los_Angeles":"(UTC-08:00) Pacific Time (US and Canada)","America\/Phoenix":"(UTC-07:00) Arizona","America\/Chihuahua":"(UTC-07:00) Chihuahua, La Paz, Mazatlan","America\/Denver":"(UTC-07:00) Mountain Time (US and Canada)","America\/Belize":"(UTC-06:00) Central America","America\/Chicago":"(UTC-06:00) Central Time (US and Canada)\t","Pacific\/Easter":"(UTC-06:00) Easter Island","America\/Mexico_City":"(UTC-06:00) Guadalajara, Mexico City, Monterrey","America\/Regina":"(UTC-06:00) Saskatchewan","America\/Bogota":"(UTC-05:00) Bogota, Lima, Quito","America\/Cancun":"(UTC-05:00) Chetumal","America\/New_York":"(UTC-05:00) Eastern Time (US and Canada)","America\/Port-au-Prince":"(UTC-05:00) Haiti","America\/Havana":"(UTC-05:00) Havana","America\/Indiana\/Indianapolis":"(UTC-05:00) Indiana (East)","America\/Asuncion":"(UTC-04:00) Asuncion","America\/Halifax":"(UTC-04:00) Atlantic Time (Canada)","America\/Caracas":"(UTC-04:00) Caracas","America\/Cuiaba":"(UTC-04:00) Cuiaba","America\/Manaus":"(UTC-04:00) Georgetown, La Paz, Manaus, San Juan","America\/Santiago":"(UTC-04:00) Santiago","America\/Grand_Turk":"(UTC-04:00) Turks and Caicos","America\/St_Johns":"(UTC-03:30) Newfoundland","America\/Fortaleza":"(UTC-03:00) Araguaina","America\/Sao_Paulo":"(UTC-03:00) Brasilia","America\/Cayenne":"(UTC-03:00) Cayenne, Fortaleza","America\/Buenos_Aires":"(UTC-03:00) City of Buenos Aires","America\/Godthab":"(UTC-03:00) Greenland","America\/Montevideo":"(UTC-03:00) Montevideo","America\/Miquelon":"(UTC-03:00) Saint Pierre and Miquelon","America\/Bahia":"(UTC-03:00) Salvador","Atlantic\/Azores":"(UTC-01:00) Azores","Atlantic\/Cape_Verde":"(UTC-01:00) Cabo Verde Islands","Africa\/Casablanca":"(UTC) Casablanca","Europe\/London":"(UTC) Dublin, Edinburgh, Lisbon, London","Etc/UTC":"(UTC) Coordinated Universal Time","Africa\/Monrovia":"(UTC) Monrovia, Reykjavik","Europe\/Amsterdam":"(UTC+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna","Europe\/Belgrade":"(UTC+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague","Europe\/Brussels":"(UTC+01:00) Brussels, Copenhagen, Madrid, Paris","Europe\/Warsaw":"(UTC+01:00) Sarajevo, Skopje, Warsaw, Zagreb","Africa\/Algiers":"(UTC+01:00) West Central Africa","Africa\/Windhoek":"(UTC+01:00) Windhoek","Asia\/Amman":"(UTC+02:00) Amman","Europe\/Athens":"(UTC+02:00) Athens, Bucharest","Asia\/Beirut":"(UTC+02:00) Beirut","Africa\/Cairo":"(UTC+02:00) Cairo","Asia\/Damascus":"(UTC+02:00) Damascus","Asia\/Gaza":"(UTC+02:00) Gaza, Hebron","Africa\/Harare":"(UTC+02:00) Harare, Pretoria","Europe\/Helsinki":"(UTC+02:00) Helsinki, Kiev, Riga, Sofia, Tallinn, Vilnius","Asia\/Istanbul":"(UTC+02:00) Istanbul","Asia\/Jerusalem":"(UTC+02:00) Jerusalem","Europe\/Kaliningrad":"(UTC+02:00) Kaliningrad","Africa\/Tripoli":"(UTC+02:00) Tripoli","Asia\/Baghdad":"(UTC+03:00) Baghdad","Asia\/Kuwait":"(UTC+03:00) Kuwait, Riyadh","Europe\/Minsk":"(UTC+03:00) Minsk","Europe\/Moscow":"(UTC+03:00) Moscow, St. Petersburg, Volgograd","Africa\/Nairobi":"(UTC+03:00) Nairobi","Asia\/Tehran":"(UTC+03:30) Tehran","Asia\/Muscat":"(UTC+04:00) Abu Dhabi, Muscat","Europe\/Astrakhan":"(UTC+04:00) Astrakhan, Ulyanovsk","Asia\/Baku":"(UTC+04:00) Baku","Europe\/Samara":"(UTC+04:00) Izhevsk, Samara","Indian\/Mauritius":"(UTC+04:00) Port Louis","Asia\/Tbilisi":"(UTC+04:00) Tbilisi","Asia\/Yerevan":"(UTC+04:00) Yerevan","Asia\/Kabul":"(UTC+04:30) Kabul","Asia\/Tashkent":"(UTC+05:00) Tashkent, Ashgabat","Asia\/Yekaterinburg":"(UTC+05:00) Ekaterinburg","Asia\/Karachi":"(UTC+05:00) Islamabad, Karachi","Asia\/Kolkata":"(UTC+05:30) Chennai, Kolkata, Mumbai, New Delhi","Asia\/Colombo":"(UTC+05:30) Sri Jayawardenepura","Asia\/Katmandu":"(UTC+05:45) Kathmandu","Asia\/Almaty":"(UTC+06:00) Astana","Asia\/Dhaka":"(UTC+06:00) Dhaka","Asia\/Novosibirsk":"(UTC+06:00) Novosibirsk","Asia\/Rangoon":"(UTC+06:30) Yangon (Rangoon)","Asia\/Bangkok":"(UTC+07:00) Bangkok, Hanoi, Jakarta","Asia\/Barnaul":"(UTC+07:00) Barnaul, Gorno-Altaysk","Asia\/Hovd":"(UTC+07:00) Hovd","Asia\/Krasnoyarsk":"(UTC+07:00) Krasnoyarsk","Asia\/Tomsk":"(UTC+07:00) Tomsk","Asia\/Chongqing":"(UTC+08:00) Beijing, Chongqing, Hong Kong SAR, Urumqi","Asia\/Irkutsk":"(UTC+08:00) Irkutsk","Asia\/Kuala_Lumpur":"(UTC+08:00) Kuala Lumpur, Singapore","Australia\/Perth":"(UTC+08:00) Perth","Asia\/Taipei":"(UTC+08:00) Taipei","Asia\/Ulaanbaatar":"(UTC+08:00) Ulaanbaatar","Asia\/Pyongyang":"(UTC+08:30) Pyongyang","Australia\/Eucla":"(UTC+08:45) Eucla","Asia\/Chita":"(UTC+09:00) Chita","Asia\/Tokyo":"(UTC+09:00) Osaka, Sapporo, Tokyo","Asia\/Seoul":"(UTC+09:00) Seoul","Asia\/Yakutsk":"(UTC+09:00) Yakutsk","Australia\/Adelaide":"(UTC+09:30) Adelaide","Australia\/Darwin":"(UTC+09:30) Darwin","Australia\/Brisbane":"(UTC+10:00) Brisbane","Australia\/Canberra":"(UTC+10:00) Canberra, Melbourne, Sydney","Pacific\/Guam":"(UTC+10:00) Guam, Port Moresby","Australia\/Hobart":"(UTC+10:00) Hobart","Asia\/Vladivostok":"(UTC+10:00) Vladivostok","Australia\/Lord_Howe":"(UTC+10:30) Lord Howe Island","Pacific\/Bougainville":"(UTC+11:00) Bougainville Island","Asia\/Srednekolymsk":"(UTC+11:00) Chokirdakh","Asia\/Magadan":"(UTC+11:00) Magadan","Pacific\/Norfolk":"(UTC+11:00) Norfolk Island","Asia\/Sakhalin":"(UTC+11:00) Sakhalin","Pacific\/Guadalcanal":"(UTC+11:00) Solomon Islands, New Caledonia","Asia\/Anadyr":"(UTC+12:00) Anadyr, Petropavlovsk-Kamchatsky","Pacific\/Auckland":"(UTC+12:00) Auckland, Wellington","Pacific\/Fiji":"(UTC+12:00) Fiji Islands","Pacific\/Chatham":"(UTC+12:45) Chatham Islands","Pacific\/Tongatapu":"(UTC+13:00) Nuku\'\'alofa","Pacific\/Apia":"(UTC+13:00) Samoa","Pacific\/Kiritimati":"(UTC+14:00) Kiritimati Island"}}\', NULL);'
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE "option" SET "category_id" = \'1\', "position" = \'6\' WHERE "category_id" = \'2\' AND "option_id" = \'14\';');
        $this->addSql('UPDATE "option" SET "position" = "position" - \'1\' WHERE "category_id" = \'2\';');
        $this->addSql('DELETE FROM "option" WHERE "option_id" = \'33\';');
    }
}