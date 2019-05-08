<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Handler\CsvImport;

use AppBundle\Component\Import\CustomCsvImport;
use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\ContactType;
use AppBundle\Entity\Country;
use AppBundle\Entity\CsvImport;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Service;
use AppBundle\Entity\State;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\Tax;
use AppBundle\Entity\User;
use AppBundle\Facade\ClientImportFacade;
use AppBundle\Facade\CsvImportFacade;
use AppBundle\Facade\ServiceFacade;
use AppBundle\Factory\ClientFactory;
use AppBundle\Factory\ServiceFactory;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeFactory;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;
use Symfony\Component\Translation\TranslatorInterface;

class ClientCsvImportHandler
{
    private const KEYS_SERVICE = [
        'invoiceLabel',
        'note',
        'individualPrice',
        'activeFrom',
        'activeTo',
        'invoicingStart',
        'invoicingPeriodType',
        'contractId',
        'contractEndDate',
        'contractLengthType',
        'fccBlockId',
        'tax1',
        'tax2',
        'tax3',
    ];

    /**
     * @var ServiceFacade
     */
    private $serviceFacade;

    /**
     * @var CsvImportFacade
     */
    private $csvImportFacade;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CustomAttributeDataProvider
     */
    private $customAttributeDataProvider;

    /**
     * @var Tariff[]
     */
    private $servicePlans = [];

    /**
     * @var State[] indexed with state.code
     */
    private $states = [];

    /**
     * @var Country[] indexed with country.name
     */
    private $countries = [];

    /**
     * @var CustomAttribute[] indexed with customAttribute.key
     */
    private $customAttributes = [];

    /**
     * @var Tax[]
     */
    private $taxes = [];

    /**
     * @var ClientImportFacade
     */
    private $clientImportFacade;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    public function __construct(
        ServiceFacade $serviceFacade,
        CsvImportFacade $csvImportFacade,
        EntityManagerInterface $entityManager,
        Options $options,
        TranslatorInterface $translator,
        CustomAttributeDataProvider $customAttributeDataProvider,
        ClientImportFacade $clientImportFacade,
        ClientFactory $clientFactory,
        ServiceFactory $serviceFactory
    ) {
        $this->serviceFacade = $serviceFacade;
        $this->csvImportFacade = $csvImportFacade;
        $this->entityManager = $entityManager;
        $this->options = $options;
        $this->translator = $translator;
        $this->customAttributeDataProvider = $customAttributeDataProvider;
        $this->clientImportFacade = $clientImportFacade;
        $this->clientFactory = $clientFactory;
        $this->serviceFactory = $serviceFactory;
    }

    public function processClientImport(array $clientsArray, Organization $organization, CsvImport $csvImport): void
    {
        // As this is called from ClientImportConsumer, which has EM clear,
        // these have to be loaded again in each run, otherwise they are not managed.
        // States and countries are just loaded again, but service plans can get changed.
        $this->servicePlans = [];

        $failed = false;
        try {
            $clients = $this->populateClients($clientsArray, $organization);
            $services = $this->populateServices($clientsArray, $organization);

            $this->clientImportFacade->handleCreateFromCsvImport($clients, $services);
        } catch (\Throwable $exception) {
            $failed = true;

            throw $exception;
        } finally {
            if ($failed) {
                $csvImport->setCountFailure($csvImport->getCountFailure() + 1);
            } else {
                $csvImport->setCountSuccess($csvImport->getCountSuccess() + 1);
            }

            $this->csvImportFacade->handleEdit($csvImport);
        }
    }

    /**
     * Takes client contacts from client's data (emails and phones) and creates saturated array of them.
     *
     * For example if we have email 1 and phone 2 filled in, but phone 1 is not filled in,
     * this method will create just one contact containing [email 1, phone 2] and so forth.
     *
     * We decided not to support creating divided contacts in CSV import, so creating two contacts
     * containing [email 1, empty phone] and [empty email, phone 2] is not possible.
     */
    public function getClientContacts(array $row): array
    {
        $emails = [];
        $phones = [];
        foreach ($row as $key => $column) {
            if (Strings::startsWith($key, 'email')) {
                $emails[] = $column;
            } elseif (Strings::startsWith($key, 'phone')) {
                $phones[] = $column;
            }
        }
        $max = max(count($emails), count($phones));
        $contacts = [];
        for ($i = 0; $i < $max; ++$i) {
            $email = $emails[$i] ?? null;
            $phone = $phones[$i] ?? null;

            if ($phone || $email) {
                $contacts[] = [
                    'email' => $email,
                    'phone' => $phone,
                ];
            }
        }

        return $contacts;
    }

    public function getStateByCode(string $stateCode): ?State
    {
        $stateCode = strtoupper($stateCode);

        // fix the state code by removing the US- prefix
        if (Strings::startsWith($stateCode, 'US-')) {
            $stateCode = str_replace('US-', '', $stateCode);
        }

        $states = $this->getStates();
        $state = array_key_exists($stateCode, $states)
            ? $states[$stateCode]
            : null;

        if ($state && ! $this->entityManager->contains($state)) {
            $state = $this->entityManager->find(State::class, $state->getId());
        }

        return $state;
    }

    public function getStateByName(string $stateName): ?State
    {
        $found = null;
        foreach ($this->getStates() as $state) {
            if (Strings::lower($state->getName()) === Strings::lower($stateName)) {
                $found = $state;

                break;
            }
        }

        if ($found && ! $this->entityManager->contains($found)) {
            $found = $this->entityManager->find(State::class, $found->getId());
        }

        return $found;
    }

    public function getCountryByName(string $countryName): ?Country
    {
        $name = Strings::lower($countryName);
        if ($name === 'usa' || $name === 'us') {
            $countryName = 'United States'; // look up value in our look up table
        }

        // init the mapping table if not exists yet
        if (empty($this->countries)) {
            foreach ($this->entityManager->getRepository(Country::class)->findAll() as $country) {
                $this->countries[$country->getName()] = $country;
            }
        }

        $country = array_key_exists($countryName, $this->countries)
            ? $this->countries[$countryName]
            : null;

        if ($country && ! $this->entityManager->contains($country)) {
            $country = $this->entityManager->find(Country::class, $country->getId());
        }

        return $country;
    }

    public function getCustomAttributeByKey(string $key): ?CustomAttribute
    {
        if (empty($this->customAttributes)) {
            $customAttributes = $this->customAttributeDataProvider->getByAttributeType(
                CustomAttribute::ATTRIBUTE_TYPE_CLIENT
            );
            foreach ($customAttributes as $customAttribute) {
                $this->customAttributes[$customAttribute->getKey()] = $customAttribute;
            }
        }

        $customAttribute = array_key_exists($key, $this->customAttributes)
            ? $this->customAttributes[$key]
            : null;

        if ($customAttribute && ! $this->entityManager->contains($customAttribute)) {
            $customAttribute = $this->entityManager->find(CustomAttribute::class, $customAttribute->getId());
        }

        return $customAttribute;
    }

    public function getServiceData($client, Organization $organization): array
    {
        if (! isset($client['servicePlan'])) {
            return [];
        }
        $serviceData = [];
        foreach ($client as $serviceKey => $item) {
            if (Strings::startsWith($serviceKey, 'service')) {
                $serviceData[Strings::firstLower(Strings::substring($serviceKey, 7))] = $item;
            }
        }
        $tariff = $this->populateTariff($serviceData['plan'], $organization);
        if ($tariff) {
            $serviceData['tariff'] = $tariff;
            $serviceData['tariffPeriod'] = $tariff->getPeriodByPeriod((int) ($client['serviceTariffPeriod'] ?? 0));
            // not persisted, used solely for validating the service
            $serviceData['client'] = new Client();
        }
        if (empty($serviceData['invoicingStart']) && ! empty($serviceData['activeFrom'])) {
            $serviceData['invoicingStart'] = $serviceData['activeFrom'];
        }

        if (isset($serviceData['invoicingStart'])) {
            $serviceData['invoicingStart'] = $this->tryParseDateTime($serviceData['invoicingStart']);
        } else {
            $serviceData['invoicingStart'] = new \DateTime();
        }
        if (isset($serviceData['activeFrom'])) {
            $serviceData['activeFrom'] = $this->tryParseDateTime($serviceData['activeFrom']);
        } else {
            $serviceData['activeFrom'] = new \DateTime();
        }
        if (isset($serviceData['activeTo'])) {
            $serviceData['activeTo'] = $this->tryParseDateTime($serviceData['activeTo']);
        }
        if (isset($serviceData['contractEndDate'])) {
            $serviceData['contractEndDate'] = $this->tryParseDateTime($serviceData['contractEndDate']);
        }

        switch (Strings::lower(trim($serviceData['invoicingPeriodType'] ?? ''))) {
            case 'b':
            case 'back':
            case 'backward':
            case 'backwards':
                $serviceData['invoicingPeriodType'] = Service::INVOICING_BACKWARDS;
                break;
            case 'f':
            case 'fwd':
            case 'forward':
            case 'forwards':
                $serviceData['invoicingPeriodType'] = Service::INVOICING_FORWARDS;
                break;
            case '':
                $serviceData['invoicingPeriodType'] = $this->options->get(Option::INVOICING_PERIOD_TYPE);
                break;
            default:
                $serviceData['invoicingPeriodType'] = $serviceData['invoicingPeriodType'] ?? null;
        }
        switch (Strings::lower(trim($serviceData['contractType'] ?? ''))) {
            case 'c':
            case 'close':
            case 'closed':
                $serviceData['contractLengthType'] = Service::CONTRACT_CLOSED;
                break;
            case 'o':
            case 'open':
            case 'opened':
            case '':
                $serviceData['contractLengthType'] = Service::CONTRACT_OPEN;
                break;
            default:
                $serviceData['contractLengthType'] = $serviceData['contractType'] ?? null;
        }

        foreach (CustomCsvImport::KEYS_TAXES as $taxId) {
            $taxName = trim($serviceData[$taxId] ?? '');
            if ($taxName !== '') {
                $serviceData[$taxId] = $this->getTaxByName($taxName) ?: $taxName;
            }
        }

        if (isset($serviceData['individualPrice'])) {
            $serviceData['individualPrice'] = (float) $serviceData['individualPrice'];
        }

        return $serviceData;
    }

    public function getTaxByName(string $taxName): ?Tax
    {
        $tax = $this->taxes[$taxName] ?? false;
        if (! $tax || ($tax instanceof Tax && ! $this->entityManager->contains($tax))) {
            $tax = $this->entityManager->getRepository(Tax::class)->findOneBy(
                [
                    'name' => $taxName,
                    'deletedAt' => null,
                ]
            );
            $this->taxes[$taxName] = $tax;
        }

        return $this->taxes[$taxName];
    }

    public function tryParseDateTime($createdDateOriginal): ?\DateTime
    {
        try {
            // import with TZ
            $createdDate = DateTimeFactory::createFromFormat(\DateTime::ATOM, $createdDateOriginal);
        } catch (\InvalidArgumentException $e) {
            try {
                // import date only
                $createdDate = DateTimeFactory::createDate($createdDateOriginal);
            } catch (\InvalidArgumentException $e) {
                try {
                    // import local datetime
                    $createdDate = DateTimeFactory::createWithoutFormat($createdDateOriginal);
                } catch (\InvalidArgumentException $e) {
                    $createdDate = null;
                }
            }
        }

        return $createdDate;
    }

    /**
     * Returns array of Client entities prepared for import.
     *
     * @return Client[]
     */
    private function populateClients(array &$data, Organization $organization): array
    {
        $entities = [];
        $usedUserNames = [];

        // $clientsFromInvalidRows and array_key_exists($row['_prevClient'], $data)
        // is needed in case the original client row has an invalid service
        // in that case it is not present in $data array and we have to handle it manually
        // if the client is invalid (i.e. not just service), this is fine as all client rows
        // will have the same errors
        $clientsFromInvalidRows = [];

        $defaultContactTypes = [
            $this->entityManager->find(ContactType::class, ContactType::IS_BILLING),
            $this->entityManager->find(ContactType::class, ContactType::IS_CONTACT),
        ];

        foreach ($data as &$row) {
            if (
                ($row['_prevClient'] ?? false) === false
                || (
                    ! array_key_exists($row['_prevClient'], $data)
                    && ! array_key_exists($row['_prevClient'], $clientsFromInvalidRows)
                )
            ) {
                $client = $this->clientFactory->create();
                $client->setOrganization($organization);
                $client->setUserIdent($row['userIdent'] ?? null);
                $client->setIsLead((bool) ($row['isLead'] ?? false));
                if (! empty($row['addressGpsLat']) && ! empty($row['addressGpsLon'])) {
                    $client->setAddressGpsLat((float) $row['addressGpsLat']);
                    $client->setAddressGpsLon((float) $row['addressGpsLon']);
                }

                $user = $client->getUser();
                $user->setRole(User::ROLE_CLIENT);

                $contacts = $this->getClientContacts($row);

                $alreadyCreatedContactWithDefaultTypes = false;
                foreach ($contacts as $contact) {
                    $contactTypes = [];

                    // only add default contact types for first contact that has an email
                    if (! $alreadyCreatedContactWithDefaultTypes && $contact['email']) {
                        $contactTypes = $defaultContactTypes;
                        $alreadyCreatedContactWithDefaultTypes = true;
                    }

                    $this->createClientContact(
                        $client,
                        $contact['email'],
                        $contact['phone'],
                        $contactTypes,
                        $usedUserNames
                    );
                }

                if (isset($row['firstName']) && ! isset($row['lastName'])) {
                    $name = explode(' ', $row['firstName'], 2);
                    if (count($name) === 2) {
                        $row['firstName'] = $name[0];
                        $row['lastName'] = $name[1];
                    }
                }
                $user->setFirstName($row['firstName'] ?? null);
                $user->setLastName($row['lastName'] ?? null);
                $user->setUsername($row['username'] ?? null);

                if (isset($row['registrationDate'])) {
                    $client->setRegistrationDate($this->tryParseDateTime($row['registrationDate']));
                }

                if (isset($row['companyName']) && $row['companyName']) {
                    $client->setClientType(Client::TYPE_COMPANY);
                    $client->setCompanyName($row['companyName'] ?? null);
                    $client->setCompanyContactFirstName($user->getFirstName());
                    $client->setCompanyContactLastName($user->getLastName());
                    $client->setCompanyRegistrationNumber($row['companyRegistrationNumber'] ?? null);
                    $client->setCompanyTaxId($row['companyTaxId'] ?? null);
                    $client->setCompanyWebsite($row['companyWebsite'] ?? null);
                    $user->setFirstName(null);
                    $user->setLastName(null);
                } else {
                    $client->setClientType(Client::TYPE_RESIDENTIAL);
                }

                $client->setStreet1($row['street1'] ?? null);
                $client->setStreet2($row['street2'] ?? null);
                $client->setCity($row['city'] ?? null);
                $client->setCountry(
                    isset($row['country']) ? $this->getCountryByName($row['country']) : null
                );
                $client->setZipCode($row['zipCode'] ?? null);

                if (isset($row['state'])) {
                    $client->setState($this->getStateByCode($row['state']));
                    if (! $client->getState()) {
                        $client->setState($this->getStateByName($row['state']));
                    }
                }

                $client->setInvoiceStreet1($row['invoiceStreet1'] ?? null);
                $client->setInvoiceStreet2($row['invoiceStreet2'] ?? null);
                $client->setInvoiceCity($row['invoiceCity'] ?? null);
                $client->setInvoiceCountry(
                    isset($row['invoiceCountry'])
                        ? $this->getCountryByName($row['invoiceCountry'])
                        : null
                );
                $client->setInvoiceZipCode($row['invoiceZipCode'] ?? null);

                if (isset($row['invoiceState'])) {
                    $client->setInvoiceState($this->getStateByCode($row['invoiceState']));
                    if (! $client->getInvoiceState()) {
                        $client->setInvoiceState($this->getStateByName($row['invoiceState']));
                    }
                }

                $taxName1 = trim($row['tax1'] ?? '');
                if ($taxName1 !== '') {
                    $client->setTax1($this->getTaxByName($taxName1));
                }

                $taxName2 = trim($row['tax2'] ?? '');
                if ($taxName2 !== '') {
                    $client->setTax2($this->getTaxByName($taxName2));
                }

                $taxName3 = trim($row['tax3'] ?? '');
                if ($taxName3 !== '') {
                    $client->setTax3($this->getTaxByName($taxName3));
                }

                $client->setInvoiceAddressSameAsContact(
                    $this->isClientInvoiceAddressSameAsContact($client)
                );

                if (
                    ($row['_prevClient'] ?? false) !== false
                    && ! array_key_exists($row['_prevClient'], $data)
                    && ! array_key_exists($row['_prevClient'], $clientsFromInvalidRows)
                ) {
                    $clientsFromInvalidRows[$row['_prevClient']] = $client;
                }

                $client->setNote($row['clientNote'] ?? null);
            } elseif (array_key_exists($row['_prevClient'], $data)) {
                $client = $data[$row['_prevClient']]['clientEntity'];
            } else {
                $client = $clientsFromInvalidRows[$row['_prevClient']];
            }

            $row['clientEntity'] = $client;
            $entities[] = $client;
        }

        return $entities;
    }

    /**
     * Returns array of Service entities prepared for import.
     *
     * @return Service[]
     */
    private function populateServices(array $data, Organization $organization): array
    {
        $serviceEntities = [];
        foreach ($data as $row) {
            if (empty($row['clientEntity'])) {
                continue;
            }
            /** @var Client $client */
            $client = $row['clientEntity'];
            $serviceData = $this->getServiceData($row, $organization);
            if (! count($serviceData)) {
                continue;
            }

            $service = $this->serviceFactory->create($client);

            if ($client->getIsLead()) {
                $service->setStatus(Service::STATUS_QUOTED);
            }
            $service->setTariff($serviceData['tariff']);
            $service->setTariffPeriod($serviceData['tariffPeriod']);
            foreach (self::KEYS_SERVICE as $key) {
                if (isset($serviceData[$key])) {
                    $setFunction = 'set' . Strings::firstUpper($key);
                    $service->$setFunction($serviceData[$key]);
                }
            }
            if (! empty($serviceData['invoiceLabel'])) {
                $service->setInvoiceLabel($serviceData['invoiceLabel']);
            }
            if (! empty($serviceData['invoicingPeriodStartDay'])) {
                $service->setInvoicingPeriodStartDay((int) ($serviceData['invoicingPeriodStartDay']));
            }
            if (! empty($serviceData['invoicingDaysInAdvance'])) {
                $service->setNextInvoicingDayAdjustment(
                    (int) ($serviceData['invoicingDaysInAdvance'] ?? $this->options->get(
                            Option::SERVICE_INVOICING_DAY_ADJUSTMENT
                        ))
                );
            }
            if (isset($serviceData['invoiceSeparately'])) {
                $service->setInvoicingSeparately((bool) $serviceData['invoiceSeparately']);
            }
            if (isset($serviceData['invoiceUseCredit'])) {
                $service->setUseCreditAutomatically((bool) $serviceData['invoiceUseCredit']);
            }
            if (isset($serviceData['invoiceApproveSendAuto'])) {
                $service->setSendEmailsAutomatically(
                    (bool) ($serviceData['invoiceApproveSendAuto'] ?? $this->options->get(
                            Option::SEND_INVOICE_BY_EMAIL
                        ))
                );
            }
            if (! empty($serviceData['minimumContractLengthMonths'])) {
                $service->setMinimumContractLengthMonths((int) $serviceData['minimumContractLengthMonths']);
            }
            if (! empty($serviceData['setupFee'])) {
                $setupFee = new Fee();
                $setupFee->setClient($client);
                $setupFee->setName(
                    $this->options->get(Option::SETUP_FEE_INVOICE_LABEL) ?? $this->translator->trans(
                        'import/Service setup fee'
                    )
                );
                $setupFee->setType(Fee::TYPE_SETUP_FEE);
                $setupFee->setPrice((float) $serviceData['setupFee']);
                $setupFee->setTaxable($this->options->get(Option::SETUP_FEE_TAXABLE));
                $setupFee->setCreatedDate($service->getActiveFrom() ?? new \DateTime());
                $service->setSetupFee($setupFee);
            }
            if (! empty($serviceData['earlyTerminationFee'])) {
                $service->setEarlyTerminationFeePrice((float) $serviceData['earlyTerminationFee']);
            }

            if (! empty($serviceData['addressGpsLat']) && ! empty($serviceData['addressGpsLon'])) {
                $service->setAddressGpsLat((float) $serviceData['addressGpsLat']);
                $service->setAddressGpsLon((float) $serviceData['addressGpsLon']);
            }

            $serviceEntities[] = $service;
        }

        return $serviceEntities;
    }

    private function createClientContact(
        Client $client,
        ?string $email,
        ?string $phone,
        array $contactTypes,
        array &$usedUserNames
    ): void {
        $contact = new ClientContact();
        $contact->setClient($client);

        foreach ($contactTypes as $contactType) {
            $contact->addType($contactType);
        }

        if (null !== $email) {
            $contact->setEmail(Strings::lower($email));

            // only check the first email for username availability, it's too much magic otherwise
            // @todo probably should be handled explicitly in the future
            if ($contactTypes) {
                $exists = $this->entityManager->getRepository(User::class)->findOneBy(
                    [
                        'username' => $contact->getEmail(),
                    ]
                );
                if (! $exists && ! array_key_exists($contact->getEmail(), $usedUserNames)) {
                    $client->getUser()->setUsername($contact->getEmail());
                    $usedUserNames[$contact->getEmail()] = true;
                }
            }
        }

        if (null !== $phone) {
            $contact->setPhone($phone);
        }

        if ($contact->getEmail() || $contact->getPhone()) {
            $client->addContact($contact);
        }
    }

    private function isClientInvoiceAddressSameAsContact(Client $client): bool
    {
        $contact = [
            $client->getStreet1(),
            $client->getStreet2(),
            $client->getCity(),
            $client->getCountry(),
            $client->getZipCode(),
        ];

        $invoice = [
            $client->getInvoiceStreet1(),
            $client->getInvoiceStreet2(),
            $client->getInvoiceCity(),
            $client->getInvoiceCountry(),
            $client->getInvoiceZipCode(),
        ];

        $diff = array_udiff(
            $contact,
            $invoice,
            function ($a, $b) {
                return $b === null ? 0 : $a <=> $b;
            }
        );

        return count($diff) === 0;
    }

    /**
     * @return State[]
     */
    private function getStates(): array
    {
        if (! $this->states) {
            foreach ($this->entityManager->getRepository(State::class)->findAll() as $state) {
                $this->states[$state->getCode()] = $state;
            }
        }

        return $this->states;
    }

    private function populateTariff($plan, Organization $organization): ?Tariff
    {
        if (array_key_exists($plan, $this->servicePlans)) {
            return $this->servicePlans[$plan];
        }

        $tariff = $this->entityManager->getRepository(Tariff::class)->findOneBy(
            [
                'name' => $plan,
                'deletedAt' => null,
                'organization' => $organization,
            ]
        );

        if ($tariff) {
            $this->servicePlans[$plan] = $tariff;
        }

        return $tariff;
    }
}
