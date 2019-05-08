<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use DateTimeInterface;
use IntlDateFormatter;
use NumberFormatter;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

class Formatter
{
    public const ALTERNATIVE = 'alternative';
    public const DEFAULT = 'default';
    public const FULL = 'full';
    public const LONG = 'long';
    public const MEDIUM = 'medium';
    public const NONE = 'none';
    public const SHORT = 'short';

    public const SECOND = 1;
    public const MINUTE = 60;
    public const HOUR = self::MINUTE * 60;
    public const DAY = self::HOUR * 24;
    public const MONTH = self::DAY * 30;
    public const YEAR = self::DAY * 365;

    public const SECOND_LABEL = 'seconds';
    public const MINUTE_LABEL = 'minutes';
    public const HOUR_LABEL = 'hours';
    public const DAY_LABEL = 'days';
    public const MONTH_LABEL = 'months';
    public const YEAR_LABEL = 'years';

    public const DURATION_LABELS = [
        self::SECOND => self::SECOND_LABEL,
        self::MINUTE => self::MINUTE_LABEL,
        self::HOUR => self::HOUR_LABEL,
        self::DAY => self::DAY_LABEL,
        self::MONTH => self::MONTH_LABEL,
        self::YEAR => self::YEAR_LABEL,
    ];

    public const DURATION_UNIT_THRESHOLDS = [
        self::YEAR => 2,
        self::MONTH => 12,
        self::DAY => 14,
        self::HOUR => 24,
        self::MINUTE => 60,
        self::SECOND => 60,
    ];

    public const DATE_FORMATTER_STYLES = [
        self::NONE => IntlDateFormatter::NONE,
        self::ALTERNATIVE => IntlDateFormatter::SHORT,
        self::MEDIUM => IntlDateFormatter::MEDIUM,
        self::LONG => IntlDateFormatter::LONG,
        self::FULL => IntlDateFormatter::FULL,
    ];

    public const NUMBER_FORMATTER_STYLES = [
        'default' => [
            NumberFormatter::PATTERN_DECIMAL,
            '#,##0.######',
        ],
        'decimal' => NumberFormatter::DECIMAL,
        'currency' => NumberFormatter::CURRENCY,
        'percent' => NumberFormatter::PERCENT,
        'scientific' => NumberFormatter::SCIENTIFIC,
        'spellout' => NumberFormatter::SPELLOUT,
        'ordinal' => NumberFormatter::ORDINAL,
        'duration' => NumberFormatter::DURATION,
    ];

    public const CURRENCY_SYMBOL_PREPEND = 0;
    public const CURRENCY_SYMBOL_APPEND = 1;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var DateTimeFormatter
     */
    private $dateTimeFormatter;

    /**
     * @var array|NumberFormatter[][]
     */
    private $numberFormatters = [];

    public function __construct(TranslatorInterface $translator, Options $options, DateTimeFormatter $dateTimeFormatter)
    {
        $this->translator = $translator;
        $this->options = $options;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * @param \DateTimeInterface|string|null $date
     * @param string                         $dateFormat
     * @param string                         $timeFormat
     *
     * @throws \Exception
     */
    public function formatDate($date, $dateFormat = 'default', $timeFormat = 'medium'): string
    {
        if (! $date instanceof \DateTimeInterface) {
            @trigger_error('Passing anything else than DateTimeInterface instance is deprecated.', E_USER_DEPRECATED);
            if ($date === null) {
                return '';
            }

            try {
                $date = new \DateTime($date);
            } catch (\Exception $e) {
                if (is_numeric($date)) {
                    $date = (new \DateTime())->setTimestamp($date);
                }
            }
        }

        assert($date instanceof DateTimeInterface);

        return trim(
            sprintf('%s %s', $this->formatDatePart($date, $dateFormat), $this->formatTimePart($date, $timeFormat))
        );
    }

    public function getMomentJsFormats(): array
    {
        return [
            'date' => [
                'default' => DateTimeFormatter::FORMAT_DATE_OPTIONS[$this->options->get(Option::FORMAT_DATE_DEFAULT)],
                'alternative' => DateTimeFormatter::FORMAT_DATE_OPTIONS[$this->options->get(
                    Option::FORMAT_DATE_ALTERNATIVE
                )],
            ],
            'time' => [
                'long' => DateTimeFormatter::FORMAT_TIME_OPTIONS_WITH_SECONDS[$this->options->get(Option::FORMAT_TIME)],
                'short' => DateTimeFormatter::FORMAT_TIME_OPTIONS[$this->options->get(Option::FORMAT_TIME)],
            ],
        ];
    }

    public function formatNumber($number, $style, ?string $locale = null): string
    {
        $number = $this->getNumberFormatter($style, true, $locale)->format($number);

        return $number !== false ? $number : '';
    }

    public function formatCurrency($number, ?string $currency, ?string $locale = null): string
    {
        if (null === $currency) {
            // Intentionally decimal instead of default.
            return $this->formatNumber($number, 'decimal', $locale);
        }

        // The replacement NumberFormatter in Symfony (for cases when ext-intl is not installed) does the rounding
        // by itself. The original NumberFormatter in PHP does not so we have to call it manually. Swiss rounding
        // is not implemented because it seems that CurrencyBundle::getRoundingIncrement() always returns 0.
        // @link Symfony\Component\Intl\NumberFormatter\NumberFormatter::roundCurrency()
        $number = round($number, Intl::getCurrencyBundle()->getFractionDigits($currency));

        return $this->getNumberFormatter('currency', true, $locale)->formatCurrency($number, $currency);
    }

    public function getCurrencyPosition(): int
    {
        $position = strpos($this->getNumberFormatter('currency')->getPattern(), "\u{00a4}");

        return $position === 0 ? self::CURRENCY_SYMBOL_PREPEND : self::CURRENCY_SYMBOL_APPEND;
    }

    public function getCurrencySymbol(string $currency): string
    {
        return trim($this->getNumberFormatter('currency', false)->formatCurrency(0, $currency), '0,. ');
    }

    private function getNumberFormatter(
        string $style,
        bool $useCustomSymbols = true,
        ?string $locale = null
    ): NumberFormatter {
        $styleKey = sprintf('%s%s', $style, $useCustomSymbols ? '/useCustomSymbols' : '');
        $locale = $locale ?: $this->translator->getLocale();
        if (
            ! array_key_exists($styleKey, $this->numberFormatters)
            || ! array_key_exists($locale, $this->numberFormatters[$styleKey])
        ) {
            $arguments = (array) self::NUMBER_FORMATTER_STYLES[$style];
            $this->numberFormatters[$styleKey][$locale] = NumberFormatter::create(
                $locale,
                ...$arguments
            );
            if ($useCustomSymbols && null !== $this->options->get(Option::FORMAT_DECIMAL_SEPARATOR)) {
                $this->numberFormatters[$styleKey][$locale]->setSymbol(
                    NumberFormatter::DECIMAL_SEPARATOR_SYMBOL,
                    $this->options->get(Option::FORMAT_DECIMAL_SEPARATOR)
                );
                $this->numberFormatters[$styleKey][$locale]->setSymbol(
                    NumberFormatter::MONETARY_SEPARATOR_SYMBOL,
                    $this->options->get(Option::FORMAT_DECIMAL_SEPARATOR)
                );
            }

            if ($useCustomSymbols && null !== $this->options->get(Option::FORMAT_THOUSANDS_SEPARATOR)) {
                $this->numberFormatters[$styleKey][$locale]->setSymbol(
                    NumberFormatter::GROUPING_SEPARATOR_SYMBOL,
                    $this->options->get(Option::FORMAT_THOUSANDS_SEPARATOR)
                );
                $this->numberFormatters[$styleKey][$locale]->setSymbol(
                    NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL,
                    $this->options->get(Option::FORMAT_THOUSANDS_SEPARATOR)
                );
            }
        }

        return $this->numberFormatters[$styleKey][$locale];
    }

    private function formatDatePart(\DateTimeInterface $date, string $format): string
    {
        switch ($format) {
            case self::FULL:
            case self::LONG:
            case self::MEDIUM:
            case self::SHORT:
                @trigger_error(sprintf('Date format "%s" is deprecated.', $format), E_USER_DEPRECATED);
            // no break
            case self::DEFAULT:
                return $this->dateTimeFormatter->formatDate($date, $this->options->get(Option::FORMAT_DATE_DEFAULT));
            case self::ALTERNATIVE:
                return $this->dateTimeFormatter->formatDate(
                    $date,
                    $this->options->get(Option::FORMAT_DATE_ALTERNATIVE)
                );
            case self::NONE:
                return '';
        }

        throw new \InvalidArgumentException(sprintf('Unknown date format "%s".', $format));
    }

    private function formatTimePart(\DateTimeInterface $date, string $format): string
    {
        switch ($format) {
            case self::FULL:
            case self::LONG:
                @trigger_error(sprintf('Time format "%s" is deprecated.', $format), E_USER_DEPRECATED);
            // no break
            case self::MEDIUM:
                return $this->dateTimeFormatter->formatTimeWithSeconds($date, $this->options->get(Option::FORMAT_TIME));
            case self::SHORT:
                return $this->dateTimeFormatter->formatTime($date, $this->options->get(Option::FORMAT_TIME));
            case self::NONE:
                return '';
        }

        throw new \InvalidArgumentException(sprintf('Unknown time format "%s".', $format));
    }
}
