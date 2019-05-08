<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataTransformer;

class DateTimeToStringTransformer extends \Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer
{
    /**
     * {@inheritdoc}
     */
    public function __construct($inputTimezone = null, $outputTimezone = null, $format = 'Y-m-d')
    {
        parent::__construct($inputTimezone, $outputTimezone, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($dateTime): string
    {
        if (is_string($dateTime)) {
            return $dateTime;
        }

        return parent::transform($dateTime);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        return parent::reverseTransform($value);
    }
}
