<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Entity\Service;
use Symfony\Component\Validator\Constraints as Assert;

class ServiceDeviceToServiceData
{
    /**
     * @var Service
     *
     * @Assert\NotNull()
     */
    public $service;
}
