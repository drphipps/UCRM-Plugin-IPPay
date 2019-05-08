<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\AuthorizeNet;

use AppBundle\Entity\Organization;

class TransactionDetailsFactory
{
    public function create(Organization $organization, bool $sandbox): TransactionDetails
    {
        $transactionDetails = new TransactionDetails();
        $transactionDetails->setOrganization($organization);
        $transactionDetails->setSandbox($sandbox);

        return $transactionDetails;
    }
}
