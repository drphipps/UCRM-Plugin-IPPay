<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\AuthorizeNet;

use AppBundle\Entity\Organization;
use AppBundle\Service\PublicUrlGenerator;
use Doctrine\ORM\EntityManager;

class CustomerInformationManagerFactory
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    public function __construct(EntityManager $em, PublicUrlGenerator $publicUrlGenerator)
    {
        $this->em = $em;
        $this->publicUrlGenerator = $publicUrlGenerator;
    }

    public function create(Organization $organization, bool $sandbox): CustomerInformationManager
    {
        $customerInformationManager = new CustomerInformationManager($this->em, $this->publicUrlGenerator);
        $customerInformationManager->setOrganization($organization);
        $customerInformationManager->setSandbox($sandbox);

        return $customerInformationManager;
    }
}
