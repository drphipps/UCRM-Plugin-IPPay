<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Component\Service\TimePeriod;
use Symfony\Component\Validator\Constraints as Assert;

class AccountStatementFilterData
{
    public const DATE_CHOICE_ALL = 'all';
    public const DATE_CHOICE_4M = '4m';

    public const DATE_CHOICES = [
        self::DATE_CHOICE_4M => 'Last 4 months',
        self::DATE_CHOICE_ALL => 'All time',
    ];

    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    public $dateFilter = self::DATE_CHOICE_4M;

    public static function getTimePeriod(string $dateFilter): TimePeriod
    {
        $startDate = null;
        $endDate = null;
        switch ($dateFilter) {
            case self::DATE_CHOICE_4M:
                // include today in calculations
                $endDate = (new \DateTimeImmutable())->modify('+1 day midnight -1 second');
                $startDate = $endDate->modify('-4 months midnight');
                break;
            case self::DATE_CHOICE_ALL:
            case '':
                return TimePeriod::allTime();
            default:
                return TimePeriod::createYear((int) $dateFilter);
        }

        return TimePeriod::create($startDate, $endDate);
    }
}
