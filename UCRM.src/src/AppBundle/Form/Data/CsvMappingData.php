<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Entity\Organization;
use Symfony\Component\Validator\Constraints as Assert;

class CsvMappingData
{
    /**
     * @var array
     */
    public $mapping = [];

    /**
     * @var Organization|null
     *
     * @Assert\NotNull()
     */
    public $organization;
}
