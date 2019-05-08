<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Util;

use Symfony\Component\Translation\TranslatorInterface;

class DurationFormatter
{
    const FULL = 'full';
    const SHORT = 'short';

    const SECOND = 1;
    const MINUTE = 60;
    const HOUR = self::MINUTE * 60;
    const DAY = self::HOUR * 24;
    const MONTH = self::DAY * 30;
    const YEAR = self::DAY * 365;

    const SECOND_LABEL = 'seconds';
    const MINUTE_LABEL = 'minutes';
    const HOUR_LABEL = 'hours';
    const DAY_LABEL = 'days';
    const MONTH_LABEL = 'months';
    const YEAR_LABEL = 'years';

    const SHORT_SECOND_LABEL = 'sec';
    const SHORT_MINUTE_LABEL = 'min';
    const SHORT_HOUR_LABEL = 'hrs';
    const SHORT_DAY_LABEL = 'd';
    const SHORT_MONTH_LABEL = 'mos';
    const SHORT_YEAR_LABEL = 'yrs';

    const DURATION_LABELS = [
        self::SECOND => self::SECOND_LABEL,
        self::MINUTE => self::MINUTE_LABEL,
        self::HOUR => self::HOUR_LABEL,
        self::DAY => self::DAY_LABEL,
        self::MONTH => self::MONTH_LABEL,
        self::YEAR => self::YEAR_LABEL,
    ];

    const SHORT_DURATION_LABELS = [
        self::SECOND => self::SHORT_SECOND_LABEL,
        self::MINUTE => self::SHORT_MINUTE_LABEL,
        self::HOUR => self::SHORT_HOUR_LABEL,
        self::DAY => self::SHORT_DAY_LABEL,
        self::MONTH => self::SHORT_MONTH_LABEL,
        self::YEAR => self::SHORT_YEAR_LABEL,
    ];

    const DURATION_UNIT_THRESHOLDS = [
        self::YEAR => 2,
        self::MONTH => 12,
        self::DAY => 14,
        self::HOUR => 24,
        self::MINUTE => 60,
        self::SECOND => 60,
    ];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function format(int $seconds, string $format = self::FULL): string
    {
        switch ($format) {
            case self::SHORT:
                $labels = self::SHORT_DURATION_LABELS;
                break;
            case self::FULL:
                $labels = self::DURATION_LABELS;
                break;
            default:
                throw new \InvalidArgumentException('Format not supported.');
        }

        $duration = [
            self::YEAR_LABEL => 0,
            self::MONTH_LABEL => 0,
            self::DAY_LABEL => 0,
            self::HOUR_LABEL => 0,
            self::MINUTE_LABEL => 0,
            self::SECOND_LABEL => 0,
        ];

        $originalSeconds = $seconds;
        foreach (self::DURATION_UNIT_THRESHOLDS as $unit => $threshold) {
            $diffThreshold = $originalSeconds / $unit;
            $diff = (int) floor($seconds / $unit);

            if ($diffThreshold >= $threshold) {
                $duration[$labels[$unit]] = $diff;
                break;
            }
            if ($diff > 0) {
                $duration[$labels[$unit]] = $diff;
                $seconds -= ($diff * $unit);
            }
        }

        $durationString = [];
        foreach ($duration as $unit => $time) {
            if ($time <= 0) {
                continue;
            }

            $durationString[] = $this->translator->transChoice(
                sprintf(
                    '%%time%% %s',
                    $unit
                ),
                $time,
                [
                    '%time%' => $time,
                ]
            );
        }

        return implode(' ', $durationString);
    }
}
