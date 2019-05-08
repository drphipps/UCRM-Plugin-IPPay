<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\AuthorizeNet;

use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityManager;

class AutomatedRecurringBillingFactory
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function create(Organization $organization, bool $sandbox): AutomatedRecurringBilling
    {
        $automatedRecurringBilling = new AutomatedRecurringBilling($this->em);
        $automatedRecurringBilling->setOrganization($organization);
        $automatedRecurringBilling->setSandbox($sandbox);

        return $automatedRecurringBilling;
    }
}
