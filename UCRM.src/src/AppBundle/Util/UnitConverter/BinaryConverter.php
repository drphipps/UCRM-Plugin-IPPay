<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util\UnitConverter;

final class BinaryConverter
{
    public const UNIT_BYTE = 'B';
    public const UNIT_KIBI = 'KiB';
    public const UNIT_MEBI = 'MiB';
    public const UNIT_GIBI = 'GiB';
    public const UNIT_TEBI = 'TiB';
    public const UNIT_PEBI = 'BiB';
    public const UNIT_EXBI = 'EiB';
    public const UNIT_ZEBI = 'ZiB';
    public const UNIT_YOBI = 'YiB';

    /**
     * @var float
     */
    private $value;

    public function __construct(float $value, string $unit)
    {
        $this->normalize($value, $unit);
    }

    public function to(string $unit): float
    {
        switch ($unit) {
            case self::UNIT_BYTE:
                return $this->value;
                break;
            case self::UNIT_KIBI:
                return $this->value / 2 ** 10;
                break;
            case self::UNIT_MEBI:
                return $this->value / 2 ** 20;
                break;
            case self::UNIT_GIBI:
                return $this->value / 2 ** 30;
                break;
            case self::UNIT_TEBI:
                return $this->value / 2 ** 40;
                break;
            case self::UNIT_PEBI:
                return $this->value / 2 ** 50;
                break;
            case self::UNIT_EXBI:
                return $this->value / 2 ** 60;
                break;
            case self::UNIT_ZEBI:
                return $this->value / 2 ** 70;
                break;
            case self::UNIT_YOBI:
                return $this->value / 2 ** 80;
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported unit \'%s\'', $unit));
                break;
        }
    }

    /**
     * Sets $this->value to basic unit.
     */
    private function normalize(float $value, string $unit): void
    {
        switch ($unit) {
            case self::UNIT_BYTE:
                $this->value = $value;
                break;
            case self::UNIT_KIBI:
                $this->value = $value * 2 ** 10;
                break;
            case self::UNIT_MEBI:
                $this->value = $value * 2 ** 20;
                break;
            case self::UNIT_GIBI:
                $this->value = $value * 2 ** 30;
                break;
            case self::UNIT_TEBI:
                $this->value = $value * 2 ** 40;
                break;
            case self::UNIT_PEBI:
                $this->value = $value * 2 ** 50;
                break;
            case self::UNIT_EXBI:
                $this->value = $value * 2 ** 60;
                break;
            case self::UNIT_ZEBI:
                $this->value = $value * 2 ** 70;
                break;
            case self::UNIT_YOBI:
                $this->value = $value * 2 ** 80;
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported unit \'%s\'', $unit));
                break;
        }
    }
}
