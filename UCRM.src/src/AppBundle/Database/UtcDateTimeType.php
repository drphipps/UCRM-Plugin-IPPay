<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Database;

use AppBundle\Util\DateTimeFactory;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;
use InvalidArgumentException;

/**
 * @deprecated use UtcDateTimeImmutableType instead
 */
class UtcDateTimeType extends DateTimeType
{
    public const NAME = 'datetime_utc';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    /**
     * @param \DateTimeInterface|string|null $value
     *
     * @return mixed|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (is_string($value)) {
            $value = new DateTime($value);
        }

        if ($value instanceof \DateTimeInterface) {
            $value = DateTimeFactory::createFromInterface($value)->setTimezone(new DateTimeZone('UTC'));
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * @return DateTime|null
     *
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value || $value instanceof DateTime) {
            return $value;
        }

        try {
            $converted = DateTimeFactory::createFromFormat(
                $platform->getDateTimeFormatString(),
                $value,
                new DateTimeZone('UTC')
            );

            $converted->setTimezone(new DateTimeZone(date_default_timezone_get()));
        } catch (InvalidArgumentException $exception) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString(),
                $exception
            );
        }

        return $converted;
    }
}
