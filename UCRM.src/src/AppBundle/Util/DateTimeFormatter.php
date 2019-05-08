<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Util;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * @internal used by Formatter, do not use directly
 */
class DateTimeFormatter
{
    public const TRANSLATION_DOMAIN = 'DateTimeFormatter';

    public const FORMAT_DATE_DAY_FIRST = 1;

    public const FORMAT_DATE_DAY_FIRST_ORDINAL = 2;
    public const FORMAT_DATE_DAY_FIRST_TWO_DIGITS = 3;
    public const FORMAT_DATE_MONTH_FIRST = 4;
    public const FORMAT_DATE_MONTH_FIRST_ORDINAL = 5;
    public const FORMAT_DATE_MONTH_FIRST_TWO_DIGITS = 6;
    public const FORMAT_DATE_SHORT_STANDARD = 7;
    public const FORMAT_DATE_SHORT_DASHES = 8;
    public const FORMAT_DATE_SHORT_DOTS = 9;
    public const FORMAT_DATE_SHORT_DOTS_TWO_DIGITS = 10;
    public const FORMAT_DATE_SHORT_DOTS_SPACES = 11;
    public const FORMAT_DATE_SHORT_DOTS_SPACES_TWO_DIGITS = 12;
    public const FORMAT_DATE_SHORT_SLASHES = 13;
    public const FORMAT_DATE_SHORT_SLASHES_TWO_DIGITS = 14;
    public const FORMAT_DATE_SHORT_SLASHES_MONTH_FIRST = 15;
    public const FORMAT_DATE_SHORT_SLASHES_MONTH_FIRST_TWO_DIGITS = 16;

    public const FORMAT_DATE_OPTIONS = [
        self::FORMAT_DATE_DAY_FIRST => 'D MMM YYYY',
        self::FORMAT_DATE_DAY_FIRST_ORDINAL => 'Do MMM YYYY',
        self::FORMAT_DATE_DAY_FIRST_TWO_DIGITS => 'DD MMM YYYY',
        self::FORMAT_DATE_MONTH_FIRST => 'MMM D, YYYY',
        self::FORMAT_DATE_MONTH_FIRST_ORDINAL => 'MMM Do, YYYY',
        self::FORMAT_DATE_MONTH_FIRST_TWO_DIGITS => 'MMM DD, YYYY',
        self::FORMAT_DATE_SHORT_STANDARD => 'YYYY-MM-DD',
        self::FORMAT_DATE_SHORT_DASHES => 'DD-MM-YYYY',
        self::FORMAT_DATE_SHORT_DOTS => 'D.M.YYYY',
        self::FORMAT_DATE_SHORT_DOTS_TWO_DIGITS => 'DD.MM.YYYY',
        self::FORMAT_DATE_SHORT_DOTS_SPACES => 'D. M. YYYY',
        self::FORMAT_DATE_SHORT_DOTS_SPACES_TWO_DIGITS => 'DD. MM. YYYY',
        self::FORMAT_DATE_SHORT_SLASHES => 'D/M/YYYY',
        self::FORMAT_DATE_SHORT_SLASHES_TWO_DIGITS => 'DD/MM/YYYY',
        self::FORMAT_DATE_SHORT_SLASHES_MONTH_FIRST => 'M/D/YYYY',
        self::FORMAT_DATE_SHORT_SLASHES_MONTH_FIRST_TWO_DIGITS => 'MM/DD/YYYY',
    ];

    public const FORMAT_TIME_12_HOURS = 1;
    public const FORMAT_TIME_24_HOURS = 2;
    public const FORMAT_TIME_OPTIONS = [
        self::FORMAT_TIME_12_HOURS => 'h:mm a',
        self::FORMAT_TIME_24_HOURS => 'H:mm',
    ];

    public const FORMAT_TIME_OPTIONS_WITH_SECONDS = [
        self::FORMAT_TIME_12_HOURS => 'h:mm:ss a',
        self::FORMAT_TIME_24_HOURS => 'H:mm:ss',
    ];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \NumberFormatter
     */
    private $ordinalNumberFormatter;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->ordinalNumberFormatter = \NumberFormatter::create(
            $translator->getLocale(),
            \NumberFormatter::ORDINAL
        );
    }

    public function formatDate(\DateTimeInterface $date, int $format): string
    {
        switch ($format) {
            case self::FORMAT_DATE_DAY_FIRST:
                return sprintf(
                    '%d %s %d',
                    $date->format('j'),
                    $this->translator->trans($date->format('M'), [], 'formatting'),
                    $date->format('Y')
                );
            case self::FORMAT_DATE_DAY_FIRST_ORDINAL:
                return sprintf(
                    '%s %s %d',
                    $this->ordinalNumberFormatter->format((int) $date->format('j')),
                    $this->translator->trans($date->format('M'), [], 'formatting'),
                    $date->format('Y')
                );
            case self::FORMAT_DATE_DAY_FIRST_TWO_DIGITS:
                return sprintf(
                    '%s %s %d',
                    $date->format('d'),
                    $this->translator->trans($date->format('M'), [], 'formatting'),
                    $date->format('Y')
                );
            case self::FORMAT_DATE_MONTH_FIRST:
                return sprintf(
                    '%s %d, %d',
                    $this->translator->trans($date->format('M'), [], 'formatting'),
                    $date->format('j'),
                    $date->format('Y')
                );
            case self::FORMAT_DATE_MONTH_FIRST_ORDINAL:
                return sprintf(
                    '%s %s, %d',
                    $this->translator->trans($date->format('M'), [], 'formatting'),
                    $this->ordinalNumberFormatter->format((int) $date->format('j')),
                    $date->format('Y')
                );
            case self::FORMAT_DATE_MONTH_FIRST_TWO_DIGITS:
                return sprintf(
                    '%s %s, %d',
                    $this->translator->trans($date->format('M'), [], 'formatting'),
                    $date->format('d'),
                    $date->format('Y')
                );
            case self::FORMAT_DATE_SHORT_STANDARD:
                return $date->format('Y-m-d');
            case self::FORMAT_DATE_SHORT_DASHES:
                return $date->format('d-m-Y');
            case self::FORMAT_DATE_SHORT_DOTS:
                return $date->format('j.n.Y');
            case self::FORMAT_DATE_SHORT_DOTS_TWO_DIGITS:
                return $date->format('d.m.Y');
            case self::FORMAT_DATE_SHORT_DOTS_SPACES:
                return $date->format('j. n. Y');
            case self::FORMAT_DATE_SHORT_DOTS_SPACES_TWO_DIGITS:
                return $date->format('d. m. Y');
            case self::FORMAT_DATE_SHORT_SLASHES:
                return $date->format('j/n/Y');
            case self::FORMAT_DATE_SHORT_SLASHES_TWO_DIGITS:
                return $date->format('d/m/Y');
            case self::FORMAT_DATE_SHORT_SLASHES_MONTH_FIRST:
                return $date->format('n/j/Y');
            case self::FORMAT_DATE_SHORT_SLASHES_MONTH_FIRST_TWO_DIGITS:
                return $date->format('m/d/Y');
            default:
                throw new \InvalidArgumentException('Unknown format.');
        }
    }

    public function formatTime(\DateTimeInterface $date, int $format): string
    {
        switch ($format) {
            case self::FORMAT_TIME_24_HOURS:
                return $date->format('G:i');
            case self::FORMAT_TIME_12_HOURS:
                return sprintf(
                    '%s %s',
                    $date->format('g:i'),
                    $this->translator->trans($date->format('a'), [], 'formatting')
                );
            default:
                throw new \InvalidArgumentException('Unknown format.');
        }
    }

    public function formatTimeWithSeconds(\DateTimeInterface $date, int $format): string
    {
        switch ($format) {
            case self::FORMAT_TIME_24_HOURS:
                return $date->format('G:i:s');
            case self::FORMAT_TIME_12_HOURS:
                return sprintf(
                    '%s %s',
                    $date->format('g:i:s'),
                    $this->translator->trans($date->format('a'), [], 'formatting')
                );
            default:
                throw new \InvalidArgumentException('Unknown format.');
        }
    }
}
