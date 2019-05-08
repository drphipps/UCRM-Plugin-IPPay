<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Email;

use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Service\Options;
use AppBundle\Util\Sleeper;

class EmailLimiter
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    /**
     * @var Sleeper
     */
    private $sleeper;

    public function __construct(Options $options, OptionsFacade $optionsFacade, Sleeper $sleeper)
    {
        $this->options = $options;
        $this->optionsFacade = $optionsFacade;
        $this->sleeper = $sleeper;
    }

    /**
     * This method should be called only after calling checkLimits().
     */
    public function increaseCounters(): void
    {
        $throttlerCounter = (int) $this->options->getGeneral(General::MAILER_THROTTLER_COUNTER, 0);
        $antifloodCounter = (int) $this->options->getGeneral(General::MAILER_ANTIFLOOD_COUNTER, 0);

        $counters = [
            General::MAILER_THROTTLER_COUNTER => ++$throttlerCounter,
            General::MAILER_ANTIFLOOD_COUNTER => ++$antifloodCounter,
            General::MAILER_ANTIFLOOD_TIMESTAMP => time(),
        ];

        if (! $this->options->getGeneral(General::MAILER_THROTTLER_TIMESTAMP)) {
            $counters[General::MAILER_THROTTLER_TIMESTAMP] = time();
        }

        $this->optionsFacade->updateGeneralMultiple($counters);
    }

    public function checkLimits(): void
    {
        $this->checkThrottler();
        $this->checkAntiflood();
    }

    /**
     * Throttler is used to prevent reaching SMTP server message limit.
     * For example Gmail has limit of 500 messages every 24 hours.
     *
     * Every time this limit is reached, the sending is paused until LIMIT_TIME seconds pass.
     */
    private function checkThrottler(): void
    {
        $countLimit = $this->options->get(Option::MAILER_THROTTLER_LIMIT_COUNT);
        $timeLimit = $this->options->get(Option::MAILER_THROTTLER_LIMIT_TIME);

        if (null === $countLimit || null === $timeLimit) {
            return;
        }

        $timestamp = (int) $this->options->getGeneral(General::MAILER_THROTTLER_TIMESTAMP);
        if (time() - $timestamp > $timeLimit) {
            if ($timeLimit > 0) {
                $this->resetThrottler();
            }

            return;
        }

        $count = (int) $this->options->getGeneral(General::MAILER_THROTTLER_COUNTER);
        if ($count < $countLimit) {
            return;
        }

        throw new EmailLimitExceededException(sprintf('Throttler activated. %s seconds until next message.', max(0, $timestamp + $timeLimit - time())));
    }

    private function resetThrottler(): void
    {
        $this->optionsFacade->updateGeneralMultiple(
            [
                General::MAILER_THROTTLER_TIMESTAMP => null,
                General::MAILER_THROTTLER_COUNTER => '0',
            ]
        );
    }

    /**
     * Antiflood is used to prevent SMTP server flooding.
     * This is managed by sleeping for some time after sending LIMIT_COUNT of emails.
     *
     * If the last message was sent at least SLEEP_TIME seconds before now,
     * the counter is reset as no antiflood is needed.
     *
     * Unlike the Throttler, timestamp of last sent message is used to check limit.
     *
     * @throws EmailAntifloodException
     */
    private function checkAntiflood(): void
    {
        $countLimit = $this->options->get(Option::MAILER_ANTIFLOOD_LIMIT_COUNT);
        $sleepTime = $this->options->get(Option::MAILER_ANTIFLOOD_SLEEP_TIME) ?? 0;

        if (null === $countLimit || $sleepTime <= 0) {
            return;
        }

        $timestamp = (int) $this->options->getGeneral(General::MAILER_ANTIFLOOD_TIMESTAMP);
        if (time() - $timestamp > $sleepTime) {
            $this->resetAntifloodCounter();

            return;
        }

        $count = (int) $this->options->getGeneral(General::MAILER_ANTIFLOOD_COUNTER);
        if ($count < $countLimit) {
            return;
        }

        throw new EmailAntifloodException(sprintf('AntiFlood activated. %s seconds until next message.', max(0, $timestamp + $sleepTime - time())));
    }

    private function resetAntifloodCounter(): void
    {
        $this->optionsFacade->updateGeneral(General::MAILER_ANTIFLOOD_COUNTER, 0);
    }
}
