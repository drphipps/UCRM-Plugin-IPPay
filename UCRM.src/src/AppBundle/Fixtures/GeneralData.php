<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Fixtures;

use AppBundle\Entity\General;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class GeneralData extends BaseFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $em)
    {
        assert($em instanceof EntityManager);
        $this->fixInvoiceTotalsMigrationsComplete($em);

        $em->flush();
    }

    public function getOrder()
    {
        return 1;
    }

    private function fixInvoiceTotalsMigrationsComplete(EntityManager $em): void
    {
        $general = $em->getRepository(General::class)->findOneBy(
            [
                'code' => General::INVOICE_TOTALS_MIGRATION_COMPLETE,
            ]
        );
        if ($general) {
            $general->setValue('1');
        }
    }
}
