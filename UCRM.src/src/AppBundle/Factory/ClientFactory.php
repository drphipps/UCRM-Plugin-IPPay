<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\ContactType;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Tax;
use AppBundle\Service\Options;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class ClientFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Options
     */
    private $options;

    public function __construct(EntityManagerInterface $entityManager, Options $options)
    {
        $this->entityManager = $entityManager;
        $this->options = $options;
    }

    public function create(?Organization $organization = null): Client
    {
        $client = new Client();
        $this->setDefaults($client, $organization);

        return $client;
    }

    private function setDefaults(Client $client, ?Organization $organization): void
    {
        $repository = $this->entityManager->getRepository(Client::class);
        $useCustomId = $this->options->get(Option::CLIENT_ID_TYPE) === Option::CLIENT_ID_TYPE_CUSTOM;
        $client->setClientType(Client::TYPE_RESIDENTIAL);
        $client->setRegistrationDate(new DateTime());

        $defaultOrganization = $organization
            ?? $this->entityManager->getRepository(Organization::class)->getSelectedOrAlone();

        if ($defaultOrganization) {
            $client->setOrganization($defaultOrganization);
            $client->setCountry($defaultOrganization->getCountry());
            if ($defaultOrganization->getState()) {
                $client->setState($defaultOrganization->getState());
            }

            if ($useCustomId) {
                $client->setUserIdent((string) $repository->getNextClientCustomId($defaultOrganization));
            }
        } elseif ($useCustomId) {
            $client->setUserIdent((string) $repository->getNextClientCustomId());
        }

        $multipleTaxes = $this->options->get(Option::PRICING_MULTIPLE_TAXES);

        $taxes = $this->entityManager->getRepository(Tax::class)->getSelected();
        $client->setTax1($taxes[0] ?? null);
        if ($multipleTaxes) {
            $client->setTax2($taxes[1] ?? null);
            $client->setTax3($taxes[2] ?? null);
        }
    }

    public function addDefaultContactIfNeeded(Client $client): void
    {
        if (! $client->getContacts()->isEmpty()) {
            return;
        }

        $contactTypeRepository = $this->entityManager->getRepository(ContactType::class);
        $billingContactType = $contactTypeRepository->find(ContactType::IS_BILLING);
        $contactContactType = $contactTypeRepository->find(ContactType::IS_CONTACT);

        $clientContact = new ClientContact();
        $clientContact->addType($billingContactType);
        $clientContact->addType($contactContactType);
        $clientContact->setIsLogin(true);
        $client->addContact($clientContact);
    }
}
