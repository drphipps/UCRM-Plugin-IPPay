<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\OrganizationBankAccount;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class OrganizationBankAccountFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->em->getRepository(OrganizationBankAccount::class)->createQueryBuilder('oba');
    }

    public function handleOrganizationBankAccountSave(OrganizationBankAccount $organizationBankAccount)
    {
        $this->em->persist($organizationBankAccount);
        $this->em->flush();
    }
}
