<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

final class ServiceDataUsageData
{
    public const EDIT_TYPE_DATE = 'date';
    public const EDIT_TYPE_PERIOD = 'period';

    /**
     * @var float
     */
    public $download = 0.0;

    /**
     * @var float
     */
    public $upload = 0.0;

    /**
     * @var \DateTime
     */
    public $date;

    /**
     * @var string
     */
    public $period;

    /**
     * @var string
     *
     * @Assert\Choice({ServiceDataUsageData::EDIT_TYPE_DATE, ServiceDataUsageData::EDIT_TYPE_PERIOD})
     */
    public $editType;
}
