<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Vendor;

class VendorRepository extends BaseRepository
{
    public function getVendors(): array
    {
        $vendors = Vendor::TYPES;
        $vendorsDb = $this->findAll();

        foreach ($vendorsDb as $vendor) {
            $vendors[$vendor->getId()] = $vendor->getName();
        }

        return array_flip($vendors);
    }
}
