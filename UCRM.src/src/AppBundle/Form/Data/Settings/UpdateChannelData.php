<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateChannelData implements SettingsDataInterface
{
    /**
     * @var string
     *
     * @Identifier(Option::UPDATE_CHANNEL)
     *
     * @Assert\Choice(choices=Option::UPDATE_CHANNELS, strict=true)
     */
    public $updateChannel;
}
