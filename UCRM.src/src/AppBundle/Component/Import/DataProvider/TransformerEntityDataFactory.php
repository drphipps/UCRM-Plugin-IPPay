<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\DataProvider;

use AppBundle\Entity\Country;
use AppBundle\Entity\State;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\Tax;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;

class TransformerEntityDataFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(): TransformerEntityData
    {
        [$statesByCode, $statesByName] = $this->getStates();
        [$countriesByCode, $countriesByName] = $this->getCountries();

        return new TransformerEntityData(
            $this->getServicePlans(),
            $statesByCode,
            $statesByName,
            $countriesByCode,
            $countriesByName,
            $this->getTaxes()
        );
    }

    /**
     * @return State[][]
     */
    private function getStates(): array
    {
        $byCode = [];
        $byName = [];

        foreach ($this->entityManager->getRepository(State::class)->findAll() as $state) {
            $byCode[Strings::lower($state->getCode())] = $state;
            $byName[Strings::lower($state->getName())] = $state;
        }

        return [$byCode, $byName];
    }

    /**
     * @return Country[][]
     */
    private function getCountries(): array
    {
        $byCode = [];
        $byName = [];

        foreach ($this->entityManager->getRepository(Country::class)->findAll() as $country) {
            $byCode[Strings::lower($country->getCode())] = $country;
            $byName[Strings::lower($country->getName())] = $country;
        }

        return [$byCode, $byName];
    }

    /**
     * @return Tax[]
     */
    private function getTaxes(): array
    {
        $taxes = [];
        foreach ($this->entityManager->getRepository(Tax::class)->findBy(['deletedAt' => null]) as $tax) {
            $taxes[Strings::lower($tax->getName())] = $tax;
        }

        return $taxes;
    }

    /**
     * @return Tariff[]
     */
    private function getServicePlans(): array
    {
        $servicePlans = [];
        foreach ($this->entityManager->getRepository(Tariff::class)->findBy(['deletedAt' => null]) as $servicePlan) {
            $servicePlans[$servicePlan->getOrganization()->getId()][Strings::lower($servicePlan->getName())]
                = $servicePlan;
        }

        return $servicePlans;
    }
}
