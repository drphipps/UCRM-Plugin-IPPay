<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\DataProvider;

use AppBundle\Entity\Country;
use AppBundle\Entity\Organization;
use AppBundle\Entity\State;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\Tax;
use Nette\Utils\Strings;

class TransformerEntityData
{
    /**
     * @var Tariff[]
     */
    private $servicePlans = [];

    /**
     * @var State[]
     */
    private $statesByCode = [];

    /**
     * @var State[]
     */
    private $statesByName = [];

    /**
     * @var Country[]
     */
    private $countriesByCode = [];

    /**
     * @var Country[]
     */
    private $countriesByName = [];

    /**
     * @var Tax[]
     */
    private $taxes = [];

    public function __construct(
        array $servicePlans,
        array $statesByCode,
        array $statesByName,
        array $countriesByCode,
        array $countriesByName,
        array $taxes
    ) {
        $this->servicePlans = $servicePlans;
        $this->statesByCode = $statesByCode;
        $this->statesByName = $statesByName;
        $this->countriesByCode = $countriesByCode;
        $this->countriesByName = $countriesByName;
        $this->taxes = $taxes;
    }

    public function getState(string $state): ?State
    {
        $state = Strings::lower(trim($state));

        // fix the state code by removing the US- prefix
        if (Strings::startsWith($state, 'us-')) {
            $state = Strings::after($state, 'us-');
        }

        return $this->statesByCode[$state] ?? $this->statesByName[$state] ?? null;
    }

    public function getCountry(string $country): ?Country
    {
        $country = Strings::lower(trim($country));
        if ($country === 'usa' || $country === 'us') {
            $country = 'united states';
        }

        return $this->countriesByName[$country] ?? $this->countriesByCode[$country] ?? null;
    }

    public function getTax(string $tax): ?Tax
    {
        return $this->taxes[Strings::lower(trim($tax))] ?? null;
    }

    public function getServicePlan(string $servicePlan, Organization $organization): ?Tariff
    {
        return $this->servicePlans[$organization->getId()][Strings::lower(trim($servicePlan))] ?? null;
    }
}
