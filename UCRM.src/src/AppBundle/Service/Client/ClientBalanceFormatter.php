<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Client;

use AppBundle\Entity\Client;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use AppBundle\Util\Formatter;

class ClientBalanceFormatter
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(Options $options, Formatter $formatter)
    {
        $this->options = $options;
        $this->formatter = $formatter;
    }

    public function getFormattedBalance(Client $client, string $currencyCode): string
    {
        return $this->formatter->formatCurrency(
            $this->getFormattedBalanceRaw($client->getBalance()),
            $currencyCode,
            $client->getOrganization()->getLocale()
        );
    }

    public function getFormattedBalanceRaw(float $balance): float
    {
        if ($this->options->get(Option::BALANCE_STYLE) === Option::BALANCE_STYLE_TYPE_US) {
            $balance *= -1;
        }

        return $balance;
    }
}
