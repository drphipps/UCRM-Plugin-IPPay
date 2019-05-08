<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Entity\Organization;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

class FccReportData
{
    /**
     * @var ArrayCollection|Organization[]
     *
     * @Assert\Count(min=1)
     */
    public $organizations;

    public function __construct()
    {
        $this->organizations = new ArrayCollection();
    }
}
