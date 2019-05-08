<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\General;
use AppBundle\Form\Data\Settings\MailerData;
use AppBundle\Form\Data\Settings\MailerLimiterData;
use AppBundle\Form\Data\Settings\TicketingData;
use AppBundle\Service\Encryption;
use AppBundle\Service\Options;
use AppBundle\Service\OptionsManager;
use Doctrine\ORM\EntityManager;

class OptionsFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var Encryption
     */
    private $encryption;

    /**
     * @var Options
     */
    private $options;

    public function __construct(
        EntityManager $em,
        OptionsManager $optionsManager,
        Encryption $encryption,
        Options $options
    ) {
        $this->em = $em;
        $this->optionsManager = $optionsManager;
        $this->encryption = $encryption;
        $this->options = $options;
    }

    public function updateGeneral(string $code, $value): void
    {
        $this->em->flush($this->handleUpdate($code, $value));
        $this->options->refresh();
    }

    public function updateGeneralMultiple(array $options): void
    {
        $flush = [];
        foreach ($options as $code => $value) {
            $flush[] = $this->handleUpdate($code, $value);
        }

        $this->em->flush($flush);
        $this->options->refresh();
    }

    public function loadMailerLimiterSettingsDefaults(MailerLimiterData $options): void
    {
        if (null !== $options->mailerAntifloodLimitCount && null !== $options->mailerAntifloodSleepTime) {
            $options->useAntiflood = true;
        }

        if (null !== $options->mailerThrottlerLimitCount && null !== $options->mailerThrottlerLimitTime) {
            $options->useThrottler = true;
        }

        if ($options->useThrottler) {
            if ($options->mailerThrottlerLimitTime % 3600 === 0) {
                $options->mailerThrottlerLimitTime = (int) ($options->mailerThrottlerLimitTime / 3600);
                $options->mailerThrottlerLimitTimeUnit = MailerLimiterData::MAILER_THROTTLER_LIMIT_TIME_UNIT_HOURS;
            } else {
                $options->mailerThrottlerLimitTime = (int) ($options->mailerThrottlerLimitTime / 60);
                $options->mailerThrottlerLimitTimeUnit = MailerLimiterData::MAILER_THROTTLER_LIMIT_TIME_UNIT_MINUTES;
            }
        }
    }

    public function handleUpdateMailerSettings(MailerData $options): void
    {
        $options->mailerPassword = $this->encryption->encrypt($options->mailerPassword ?? '');
        $this->optionsManager->updateOptions($options);
    }

    public function handleUpdateMailerLimiterSettings(MailerLimiterData $options): void
    {
        if (! $options->useAntiflood) {
            $options->mailerAntifloodLimitCount = null;
            $options->mailerAntifloodSleepTime = null;
        }

        if (! $options->useThrottler) {
            $options->mailerThrottlerLimitCount = null;
            $options->mailerThrottlerLimitTime = null;
        } else {
            switch ($options->mailerThrottlerLimitTimeUnit) {
                case MailerLimiterData::MAILER_THROTTLER_LIMIT_TIME_UNIT_HOURS:
                    $options->mailerThrottlerLimitTime = $options->mailerThrottlerLimitTime * 3600;
                    break;
                case MailerLimiterData::MAILER_THROTTLER_LIMIT_TIME_UNIT_MINUTES:
                    $options->mailerThrottlerLimitTime = $options->mailerThrottlerLimitTime * 60;
                    break;
            }
        }

        $this->optionsManager->updateOptions($options);
    }

    public function handleUpdateTicketingSetting(TicketingData $options): void
    {
        $this->optionsManager->updateOptions($options);
    }

    private function handleUpdate(string $code, $value): General
    {
        $general = $this->em->getRepository(General::class)->findOneBy(
            [
                'code' => $code,
            ]
        );
        $general = $general ?? $this->createGeneral($code);
        $general->setValue($value);

        return $general;
    }

    private function createGeneral(string $code): General
    {
        $general = new General();
        $general->setCode($code);
        $this->em->persist($general);

        return $general;
    }
}
