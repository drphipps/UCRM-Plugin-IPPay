<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180712121952 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('
                  INSERT INTO currency (name, code, symbol) 
                  VALUES  
                        (\'Emirati dirham\', \'AED\', \'د.إ\'),
                        (\'Burundian franc\', \'BIF\', \'FBu\'),
                        (\'Congolese franc\', \'CDF\', \'FC\'),
                        (\'Cape Verde escudo\', \'CVE\', \'$\'),
                        (\'Djiboutian franc\', \'DJF\', \'Fdj\'),
                        (\'Ethiopian birr\', \'ETB\', \'Br\'),
                        (\'Georgian lari\', \'GEL\', \'₾\'),
                        (\'Ghanaian cedi\', \'GHS\', \'GH₵\'),
                        (\'Gambian dalasi\', \'GMD\', \'D\'),
                        (\'Guinean franc\', \'GNF\', \'FG\'),
                        (\'Haitian gourde\', \'HTG\', \'G\'),
                        (\'Comoro franc\', \'KMF\', \'CF\'),
                        (\'Lesotho loti\', \'LSL\', \'L\'),
                        (\'Moldovan leu\', \'MDL\', \'L\'),
                        (\'Malagasy ariary\', \'MGA\', \'Ar\'),
                        (\'Myanmar kyat\', \'MMK\', \'K\'),
                        (\'Macanese pataca\', \'MOP\', \'MOP$\'),
                        (\'Maldivian rufiyaa\', \'MVR\', \'Rf\'),
                        (\'Malawian kwacha\', \'MWK\', \'K\'),
                        (\'Rwandan franc\', \'RWF\', \'FRw\'),
                        (\'Tajikistani somoni\', \'TJS\', \'SM\'),
                        (\'Tongan paʻanga\', \'TOP\', \'T$\'),
                        (\'Ugandan shilling\', \'UGX\', \'USh\'),
                        (\'Venezuelan bolívar\', \'VEB\', \'USh\'),
                        (\'Samoan tala\', \'WST\', \'WS$\'),
                        (\'CFP franc\', \'XPF\', \'CFP\'),
                        (\'Zambian kwacha\', \'ZMW\', \'ZK\')
        ');

        $this->addSql('ALTER TABLE currency ADD obsolete BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('UPDATE currency SET obsolete = true WHERE code IN (\'GHC\', \'TRL\')');

        $this->addSql('ALTER TABLE payment_plan RENAME COLUMN amount_in_cents TO amount_in_smallest_unit');
        $this->addSql('ALTER TABLE payment_plan ADD smallest_unit_multiplier SMALLINT DEFAULT 100 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('DELETE FROM currency WHERE code IN (
                        \'AED\',
                        \'BIF\',
                        \'CDF\',
                        \'CVE\',
                        \'DJF\',
                        \'ETB\',
                        \'GEL\',
                        \'GMD\',
                        \'GNF\',
                        \'HTG\',
                        \'KMF\',
                        \'LSL\',
                        \'MDL\',
                        \'MGA\',
                        \'MMK\',
                        \'MOP\',
                        \'MVR\',
                        \'MWK\',
                        \'RWF\',
                        \'TJS\',
                        \'TOP\',
                        \'UGX\',
                        \'WST\',
                        \'XPF\',
                        \'ZMW\')
        ');

        $this->addSql('ALTER TABLE currency DROP obsolete');

        $this->addSql('ALTER TABLE payment_plan RENAME COLUMN amount_in_smallest_unit TO amount_in_cents');
        $this->addSql('ALTER TABLE payment_plan DROP smallest_unit_multiplier');
    }
}
