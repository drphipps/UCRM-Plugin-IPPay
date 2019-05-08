<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Database;

use AppBundle\Util\DateTimeImmutableFactory;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;
use InvalidArgumentException;

class UtcDateTimeImmutableType extends DateTimeType
{
    public const NAME = 'datetime_immutable_utc';

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
            $value = new DateTimeImmutable($value);
        }

        if ($value instanceof \DateTimeInterface) {
            $value = DateTimeImmutableFactory::createFromInterface($value)->setTimezone(new DateTimeZone('UTC'));
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * @return DateTimeImmutable|null
     *
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value || $value instanceof DateTimeImmutable) {
            return $value;
        }

        try {
            $converted = DateTimeImmutableFactory::createFromFormat(
                $platform->getDateTimeFormatString(),
                $value,
                new DateTimeZone('UTC')
            );

            $converted = $converted->setTimezone(new DateTimeZone(date_default_timezone_get()));
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
