<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Locale;
use AppBundle\Entity\Option;
use AppBundle\Entity\Timezone;
use Symfony\Component\Validator\Constraints as Assert;

final class LocalizationData implements SettingsDataInterface
{
    /**
     * @var string|Locale
     *
     * @Identifier(Option::APP_LOCALE)
     */
    public $appLocale;

    /**
     * @var string|Timezone
     *
     * @Identifier(Option::APP_TIMEZONE)
     */
    public $appTimezone;

    /**
     * @var int
     *
     * @Identifier(Option::FORMAT_DATE_DEFAULT)
     */
    public $formatDateDefault;

    /**
     * @var int
     *
     * @Identifier(Option::FORMAT_DATE_ALTERNATIVE)
     */
    public $formatDateAlternative;

    /**
     * @var int
     *
     * @Identifier(Option::FORMAT_TIME)
     */
    public $formatTime;

    /**
     * @var bool
     */
    public $formatUseDefaultDecimalSeparator = false;

    /**
     * @var string|null
     *
     * @Identifier(Option::FORMAT_DECIMAL_SEPARATOR)
     *
     * @Assert\Length(max=1)
     */
    public $formatDecimalSeparator;

    /**
     * @var bool
     */
    public $formatUseDefaultThousandsSeparator = false;

    /**
     * @var string|null
     *
     * @Identifier(Option::FORMAT_THOUSANDS_SEPARATOR)
     *
     * @Assert\Length(max=1)
     */
    public $formatThousandsSeparator;
}
