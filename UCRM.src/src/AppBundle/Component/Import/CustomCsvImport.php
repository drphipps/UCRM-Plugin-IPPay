<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import;

use AppBundle\Component\Validator\EntityValidator;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientAttribute;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\CsvImport;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Entity\Tax;
use AppBundle\Entity\User;
use AppBundle\Facade\CsvImportFacade;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Form\CsvImportPaymentType;
use AppBundle\Handler\CsvImport\ClientCsvImportHandler;
use AppBundle\RabbitMq\ClientImport\ClientImportMessage;
use AppBundle\RabbitMq\PaymentImport\PaymentImportMessage;
use AppBundle\Repository\ClientRepository;
use AppBundle\Util\DateTimeFactory;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;
use RabbitMqBundle\RabbitMqEnqueuer;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @deprecated Client CSV import was refactored, this class is obsolete and only used for Payment import, which is not yet refactored
 * @see https://ubnt.myjetbrains.com/youtrack/issue/UCRM-2807
 */
class CustomCsvImport
{
    public const CLIENTS_CTRL_SESSION_KEY = 'CustomCsvImport_Clients_Ctrl';
    public const CLIENTS_SESSION_KEY = 'CustomCsvImport_Clients';
    public const CLIENTS_SESSION_ORGANIZATION = 'CustomCsvImport_Clients_Organization';
    public const PAYMENTS_CTRL_SESSION_KEY = 'CustomCsvImport_Payments_Ctrl';
    public const PAYMENTS_SESSION_KEY = 'CustomCsvImport_Payments';

    public const KEYS_CLIENTS = [
        'userIdent',
        'firstName',
        'lastName',
        'nameForView',
        'companyName',
        'isLead',
        'companyRegistrationNumber',
        'companyTaxId',
        'tax1',
        'tax2',
        'tax3',
        'companyWebsite',
        'addressGpsLat',
        'addressGpsLon',
        'username',
        'email1',
        'email2',
        'email3',
        'emails',
        'phone1',
        'phone2',
        'phone3',
        'phones',
        'street1',
        'street2',
        'city',
        'country',
        'state',
        'zipCode',
        'invoiceStreet1',
        'invoiceStreet2',
        'invoiceCity',
        'invoiceCountry',
        'invoiceState',
        'invoiceZipCode',
        'registrationDate',
        'servicePlan',
        'serviceInvoiceLabel',
        'serviceNote',
        'serviceTariffPeriod',
        'serviceIndividualPrice',
        'serviceActiveFrom',
        'serviceActiveTo',
        'serviceInvoicingStart',
        'serviceInvoicingPeriodType',
        'serviceInvoicingPeriodStartDay',
        'serviceInvoicingDaysInAdvance',
        'serviceInvoiceSeparately',
        'serviceInvoiceUseCredit',
        'serviceInvoiceApproveSendAuto',
        'serviceContractId',
        'serviceContractType',
        'serviceContractEndDate',
        'serviceFccBlockId',
        'serviceMinimumContractLengthMonths',
        'serviceTax1',
        'serviceTax2',
        'serviceTax3',
        'serviceSetupFee',
        'serviceEarlyTerminationFee',
        'serviceAddressGpsLat',
        'serviceAddressGpsLon',
        'clientNote',
    ];

    public const LABELS_CLIENTS = [
        'userIdent' => 'import/Custom ID',
        'firstName' => 'import/First name',
        'lastName' => 'import/Last name',
        'nameForView' => 'import/Name (first and last separated by space)',
        'companyName' => 'import/Company name',
        'isLead' => 'Is lead',
        'addressGpsLat' => 'import/Client latitude',
        'addressGpsLon' => 'import/Client longitude',
        'companyRegistrationNumber' => 'import/Company registration number',
        'companyTaxId' => 'import/Company tax ID',
        'tax1' => 'import/Client tax 1',
        'tax2' => 'import/Client tax 2',
        'tax3' => 'import/Client tax 3',
        'companyWebsite' => 'import/Company website',
        'username' => 'Username',
        'email1' => 'import/Email (primary)',
        'email2' => 'import/Email (secondary)',
        'email3' => 'import/Email (tertiary)',
        'emails' => 'import/Emails (separated by comma)',
        'phone1' => 'import/Phone (primary)',
        'phone2' => 'import/Phone (secondary)',
        'phone3' => 'import/Phone (tertiary)',
        'phones' => 'import/Phones (separated by comma)',
        'street1' => 'import/Street',
        'street2' => 'import/Street 2',
        'city' => 'import/City',
        'country' => 'import/Country',
        'state' => 'import/State',
        'zipCode' => 'import/ZIP code',
        'invoiceStreet1' => 'import/Invoice street',
        'invoiceStreet2' => 'import/Invoice street 2',
        'invoiceCity' => 'import/Invoice city',
        'invoiceCountry' => 'import/Invoice country',
        'invoiceState' => 'import/Invoice state',
        'invoiceZipCode' => 'import/Invoice ZIP code',
        'registrationDate' => 'import/Registration date',
        'servicePlan' => 'import/Service plan',
        'serviceInvoiceLabel' => 'import/Service invoice label',
        'serviceNote' => 'import/Service note',
        'serviceTariffPeriod' => 'import/Service period in months',
        'serviceIndividualPrice' => 'import/Service individual price',
        'serviceActiveFrom' => 'import/Service active from',
        'serviceActiveTo' => 'import/Service active to',
        'serviceInvoicingStart' => 'import/Service invoicing from',
        'serviceInvoicingPeriodType' => 'import/Service invoicing type',
        'serviceInvoicingPeriodStartDay' => 'import/Service invoicing period start day',
        'serviceInvoicingDaysInAdvance' => 'import/Service create invoice X days in advance',
        'serviceInvoiceSeparately' => 'import/Service invoice separately',
        'serviceInvoiceUseCredit' => 'import/Service invoice use credit automatically',
        'serviceInvoiceApproveSendAuto' => 'import/Service invoice approve and send automatically',
        'serviceContractId' => 'import/Service contract ID',
        'serviceContractType' => 'import/Service contract type (open/closed)',
        'serviceContractEndDate' => 'import/Service contract end date',
        'serviceMinimumContractLengthMonths' => 'import/Service minimum contract length (months)',
        'serviceSetupFee' => 'import/Service setup fee',
        'serviceEarlyTerminationFee' => 'import/Service early termination fee',
        'serviceTaxable' => 'import/Service taxable',
        'serviceTax1' => 'import/Service tax 1',
        'serviceTax2' => 'import/Service tax 2',
        'serviceTax3' => 'import/Service tax 3',
        'serviceFccBlockId' => 'import/Service Census Block GEOID',
        'serviceAddressGpsLat' => 'import/Service latitude',
        'serviceAddressGpsLon' => 'import/Service longitude',
        'clientNote' => 'import/Client note',
    ];

    private const KEYS_PAYMENTS = [
        'amount',
        'currency',
        'method',
        'client',
        'createdDate',
        'note',
    ];

    public const KEYS_TAXES = [
        'tax1',
        'tax2',
        'tax3',
    ];

    public const LABELS_PAYMENTS = [
        'amount' => 'Amount',
        'currency' => 'Currency',
        'method' => 'Method',
        'client' => 'Client',
        'createdDate' => 'Created date',
        'note' => 'Note',
    ];

    public const CLIENT_MATCH_BY_ID = 'ucrm/id';
    public const CLIENT_MATCH_BY_CUSTOM_ID = 'ucrm/custom_id';

    public const LIMIT_IMPORT_CHOOSE = 100;

    /**
     * @var File
     */
    private $file;

    /**
     * @var array
     */
    private $fieldsMap = [];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ClientRepository|ObjectRepository
     */
    private $clientRepository;

    /**
     * @var Currency[] indexed with currency.code
     */
    private $currenciesByCode = [];

    /**
     * @var Currency[] indexed with currency.id
     */
    private $currenciesById = [];

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var bool
     */
    private $skipHeader = false;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var CsvImporter
     */
    private $csvImporter;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string[]
     */
    private $missingPlans = [];

    /**
     * @var string[]
     */
    private $missingTaxes = [];

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var ClientCsvImportHandler
     */
    private $clientCsvImportHandler;

    /**
     * @var CsvImportFacade
     */
    private $csvImportFacade;

    /**
     * @var array
     */
    private $fields = [];

    public function __construct(
        File $file,
        array $ctrl,
        EntityManager $entityManager,
        ValidatorInterface $validator,
        PaymentFacade $paymentFacade,
        TranslatorInterface $translator,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        ClientCsvImportHandler $clientCsvImportHandler,
        CsvImportFacade $csvImportFacade
    ) {
        $this->file = $file;
        $this->csvImporter = new CsvImporter($file, $ctrl);
        $this->entityManager = $entityManager;
        $this->clientRepository = $this->entityManager->getRepository(Client::class);
        $this->validator = $validator;
        $this->paymentFacade = $paymentFacade;
        $this->translator = $translator;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->clientCsvImportHandler = $clientCsvImportHandler;
        $this->csvImportFacade = $csvImportFacade;

        $this->skipHeader = $ctrl[$this->csvImporter::FIELD_HAS_HEADER];
    }

    /**
     * Gets array of CSV columns. Used for fields map settings.
     */
    public function getFieldsForMap(bool $includePlaceholder = true): array
    {
        if (! $this->fields) {
            $csvControl = $this->csvImporter->getCsvControl();
            $file = $this->file->openFile();
            $file->setCsvControl(
                $csvControl[$this->csvImporter::FIELD_DELIMITER],
                $csvControl[$this->csvImporter::FIELD_ENCLOSURE],
                $csvControl[$this->csvImporter::FIELD_ESCAPE]
            );
            $fields = $file->fgetcsv();
            if ($fields === false) {
                $fields = [];
            }

            $this->fields = array_filter(
                $fields,
                function ($value) {
                    return $value !== null;
                }
            );
        }

        if (! $includePlaceholder) {
            return $this->fields;
        }

        return array_merge(
            [
                '' => 'Choose column...',
            ],
            $this->fields
        );
    }

    public function getFieldsHash(): string
    {
        return sha1(implode(',', $this->getFieldsForMap(false)));
    }

    public function getFieldsMap(): array
    {
        return $this->fieldsMap;
    }

    public function setFieldsMap(array $map): void
    {
        $this->fieldsMap = $map;
    }

    /**
     * Tries to guess fields map according to column names.
     */
    public function guessFieldsMapClient(array $fields): array
    {
        $config = array_fill_keys(self::KEYS_CLIENTS, '');
        foreach ($fields as $key => $column) {
            if ($key === '' || in_array($key, $config, true)) {
                continue;
            }

            $name = preg_replace('/\([^)]*\)/', '', $column);
            $name = Strings::lower(
                strtr(
                    $name,
                    [
                        '.' => '',
                        '-' => '',
                        '_' => '',
                        ' ' => '',
                    ]
                )
            );

            switch ($name) {
                case 'id':
                case 'clientid':
                case 'ident':
                case 'userident':
                case 'customid':
                    $config['userIdent'] = $key;
                    break;
                case 'name':
                case 'firstname':
                    $config['firstName'] = $key;
                    break;
                case 'lastname':
                case 'surname':
                    $config['lastName'] = $key;
                    break;
                case 'fullname':
                    $config['nameForView'] = $key;
                    break;
                case 'username':
                    $config['username'] = $key;
                    break;
                case 'company':
                case 'companyname':
                    $config['companyName'] = $key;
                    break;
                case 'lead':
                case 'islead':
                case 'clientlead':
                    $config['isLead'] = $key;
                    break;
                case 'lat':
                case 'latitude':
                case 'clientlatitude':
                    $config['addressGpsLat'] = $key;
                    break;
                case 'lon':
                case 'longitude':
                case 'clientlongitude':
                    $config['addressGpsLon'] = $key;
                    break;
                case 'registrationnumber':
                case 'companyregistrationnumber':
                    $config['companyRegistrationNumber'] = $key;
                    break;
                case 'tax':
                case 'clienttax':
                case 'clienttaxrate':
                case 'tax1':
                case 'clienttax1':
                case 'clienttaxrate1':
                case 'defaulttax':
                case 'taxdefault':
                    $config['tax1'] = $key;
                    break;
                case 'clienttax2':
                case 'clienttaxrate2':
                case 'tax2':
                    $config['tax2'] = $key;
                    break;
                case 'clienttax3':
                case 'clienttaxrate3':
                case 'tax3':
                    $config['tax3'] = $key;
                    break;
                case 'taxid':
                case 'companytaxid':
                    $config['companyTaxId'] = $key;
                    break;
                case 'web':
                case 'website':
                case 'companywebsite':
                    $config['companyWebsite'] = $key;
                    break;
                case 'email':
                case 'email1':
                    $config['email1'] = $key;
                    break;
                case 'email2':
                    $config['email2'] = $key;
                    break;
                case 'email3':
                    $config['email3'] = $key;
                    break;
                case 'emails':
                    $config['emails'] = $key;
                    break;
                case 'phone':
                case 'telephone':
                case 'tel':
                case 'phone1':
                    $config['phone1'] = $key;
                    break;
                case 'phone2':
                case 'mobile':
                    $config['phone2'] = $key;
                    break;
                case 'phone3':
                case 'fax':
                    $config['phone3'] = $key;
                    break;
                case 'phones':
                    $config['phones'] = $key;
                    break;
                case 'street':
                case 'street1':
                    $config['street1'] = $key;
                    break;
                case 'street2':
                    $config['street2'] = $key;
                    break;
                case 'city':
                    $config['city'] = $key;
                    break;
                case 'country':
                    $config['country'] = $key;
                    break;
                case 'state':
                    $config['state'] = $key;
                    break;
                case 'zip':
                case 'zipcode':
                case 'postalcode':
                    $config['zipCode'] = $key;
                    break;
                case 'invoicestreet':
                case 'invoicestreet1':
                    $config['invoiceStreet1'] = $key;
                    break;
                case 'invoicestreet2':
                    $config['invoiceStreet2'] = $key;
                    break;
                case 'invoicecity':
                    $config['invoiceCity'] = $key;
                    break;
                case 'invoicecountry':
                    $config['invoiceCountry'] = $key;
                    break;
                case 'invoicestate':
                    $config['invoiceState'] = $key;
                    break;
                case 'invoicezip':
                case 'invoicezipcode':
                case 'invoicepostalcode':
                    $config['invoiceZipCode'] = $key;
                    break;
                case 'date':
                case 'registered':
                case 'registrationdate':
                    $config['registrationDate'] = $key;
                    break;
                case 'service':
                case 'tariff':
                case 'serviceplan':
                case 'plan':
                    $config['servicePlan'] = $key;
                    break;
                case 'serviceinvoicelabel':
                case 'servicecustomlabel':
                    $config['serviceInvoiceLabel'] = $key;
                    break;
                case 'servicenote':
                    $config['serviceNote'] = $key;
                    break;
                case 'period':
                case 'serviceperiod':
                case 'planperiod':
                case 'tariffperiod':
                    $config['serviceTariffPeriod'] = $key;
                    break;
                case 'price':
                case 'serviceprice':
                case 'serviceindividualprice':
                case 'individualprice':
                case 'planprice':
                case 'tariffprice':
                    $config['serviceIndividualPrice'] = $key;
                    break;
                case 'serviceactivefrom':
                    $config['serviceActiveFrom'] = $key;
                    break;
                case 'serviceactiveto':
                    $config['serviceActiveTo'] = $key;
                    break;
                case 'serviceinvoicingfrom':
                case 'serviceinvoicingstarts':
                case 'serviceinvoicingstart':
                case 'serviceinvoicingbegin':
                    $config['serviceInvoicingStart'] = $key;
                    break;
                case 'serviceinvoicing':
                case 'serviceinvoicingtype':
                case 'serviceinvoicingperiodtype':
                case 'serviceinvoicingtypeperiod':
                    $config['serviceInvoicingPeriodType'] = $key;
                    break;
                case 'serviceinvoicingperiodstartday':
                    $config['serviceInvoicingPeriodStartDay'] = $key;
                    break;
                case 'servicecreateinvoicexdaysinadvance':
                case 'serviceinvoicingdaysinadvance':
                    $config['serviceInvoicingDaysInAdvance'] = $key;
                    break;
                case 'serviceinvoiceseparately':
                    $config['serviceInvoiceSeparately'] = $key;
                    break;
                case 'serviceinvoiceusecredit':
                case 'serviceinvoiceusecreditautomatically':
                    $config['serviceInvoiceUseCredit'] = $key;
                    break;
                case 'serviceinvoiceapproveandsendautomatically':
                case 'serviceinvoiceapprovesendauto':
                    $config['serviceInvoiceApproveSendAuto'] = $key;
                    break;
                case 'servicegeoid':
                case 'servicecensusgeoid':
                case 'servicecensus':
                case 'serviceblockgeoid':
                case 'servicecensusblockgeoid':
                case 'servicefccblockid':
                    $config['serviceFccBlockId'] = $key;
                    break;
                case 'servicecontractid':
                    $config['serviceContractId'] = $key;
                    break;
                case 'servicecontracttype':
                    $config['serviceContractType'] = $key;
                    break;
                case 'servicecontractenddate':
                    $config['serviceContractEndDate'] = $key;
                    break;
                case 'serviceminimumcontractlength':
                    $config['serviceMinimumContractLengthMonths'] = $key;
                    break;
                case 'servicesetupfee':
                    $config['serviceSetupFee'] = $key;
                    break;
                case 'serviceearlyterminationfee':
                    $config['serviceEarlyTerminationFee'] = $key;
                    break;
                case 'servicetax':
                case 'servicetaxrate':
                case 'servicetax1':
                case 'servicetaxrate1':
                    $config['serviceTax1'] = $key;
                    break;
                case 'servicetax2':
                case 'servicetaxrate2':
                    $config['serviceTax2'] = $key;
                    break;
                case 'servicetax3':
                case 'servicetaxrate3':
                    $config['serviceTax3'] = $key;
                    break;
                case 'latitudeservice':
                case 'servicelatitude':
                    $config['serviceAddressGpsLat'] = $key;
                    break;
                case 'longitudeservice':
                case 'servicelongitude':
                    $config['serviceAddressGpsLon'] = $key;
                    break;
                case 'clientnote':
                case 'note':
                    $config['clientNote'] = $key;
            }
        }

        return $config;
    }

    /**
     * Tries to guess fields map according to column names.
     */
    public function guessFieldsMapPayment(array $fields): array
    {
        $config = array_fill_keys(self::KEYS_PAYMENTS, '');
        foreach ($fields as $key => $column) {
            if ($key === '' || in_array($key, $config, true)) {
                continue;
            }

            $name = Strings::lower(
                strtr(
                    $column,
                    [
                        '.' => '',
                        '-' => '',
                        '_' => '',
                        ' ' => '',
                    ]
                )
            );

            switch ($name) {
                case 'amount(numeric)':
                case 'amount':
                case 'price':
                    $config['amount'] = $key;
                    break;
                case 'currency':
                    $config['currency'] = $key;
                    break;
                case 'method':
                    $config['method'] = $key;
                    break;
                case 'date':
                case 'createddate':
                    $config['createdDate'] = $key;
                    break;
                case 'note':
                    $config['note'] = $key;
                    break;
                case 'client':
                case 'clientid':
                case 'customer':
                case 'customerid':
                    $config['client'] = $key;
                    break;
            }
        }

        // If we can't match currency to a column, pre-fill default organization's currency
        if ($config['currency'] === '') {
            $organization = $this->entityManager->getRepository(Organization::class)
                ->getFirstSelected();
            if ($organization) {
                $config['currency'] = CsvImportPaymentType::PREFIX_CURRENCY . $organization->getCurrency()->getId();
            }
        }

        return $config;
    }

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    /**
     * Enqueues client import to RabbitMQ and returns count of unique clients, that will be imported.
     * Processes actual client import (saves data to database) and returns count of saved clients.
     */
    public function enqueueClientImport(array $clientsArray, User $user): int
    {
        $clientMessages = [];

        // Goes through the clients data and merges all services under single client.
        // The "_merged" key is then used to create message for RabbitMQ consumer
        // and can be used by processClientImport() without any further modifications.
        //
        // It is IMPORTANT to use the original $key in the "_merged" array,
        // because _prevClient holds the row indexes from it.
        foreach ($clientsArray as $key => $row) {
            if (
                ($row['_prevClient'] ?? false) !== false
            ) {
                if (array_key_exists($row['_prevClient'], $clientMessages)) {
                    $clientMessages[$row['_prevClient']]['_merged'][$key] = $row;
                } else {
                    $clientMessages[$row['_prevClient']] = $row;
                    $clientMessages[$row['_prevClient']]['_merged'][$key] = $row;
                }
            } else {
                $clientMessages[$key] = $row;
                $clientMessages[$key]['_merged'][$key] = $row;
            }
        }

        $count = count($clientMessages);
        $csvImport = new CsvImport();
        $csvImport->setCount($count);
        $csvImport->setUser($user);
        $csvImport->setType(CsvImport::TYPE_CLIENT);
        $this->csvImportFacade->handleNew($csvImport);

        foreach ($clientMessages as $data) {
            $this->rabbitMqEnqueuer->enqueue(
                new ClientImportMessage(
                    $data['_merged'],
                    $this->organization->getId(),
                    $csvImport->getUuid()
                )
            );
        }

        return $count;
    }

    /**
     * Returns array of filled client rows from CSV.
     * Each client array gets fullName and address fields (these are used only for data preview).
     */
    public function getClients(): array
    {
        $clients = [];

        $csvControl = $this->csvImporter->getCsvControl();
        $file = $this->file->openFile();
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(
            $csvControl[$this->csvImporter::FIELD_DELIMITER],
            $csvControl[$this->csvImporter::FIELD_ENCLOSURE],
            $csvControl[$this->csvImporter::FIELD_ESCAPE]
        );

        $map = $this->fieldsMap;
        $map = array_filter(
            $map,
            function ($value) {
                return $value !== '';
            }
        );

        $skippedHeader = false;
        $rowNumber = 1;
        foreach ($file as $row) {
            if ($this->skipHeader && ! $skippedHeader) {
                $skippedHeader = true;
                ++$rowNumber;
                continue;
            }

            $client = [];

            foreach ($map as $field => $column) {
                $client[$field] = $row[$column] ?? '';
            }

            $client = array_filter(
                $client,
                function ($value) {
                    return $value !== '';
                }
            );

            if (count($client) > 0) {
                $address = [
                    $client['street1'] ?? null,
                    $client['street2'] ?? null,
                    $client['city'] ?? null,
                    $client['zipCode'] ?? null,
                    $client['state'] ?? null,
                    $client['country'] ?? null,
                ];

                $client['address'] = implode(', ', array_filter($address));
                $client['address'] = $client['address'] ?: null;

                $invoiceAddress = [
                    $client['invoiceStreet1'] ?? null,
                    $client['invoiceStreet2'] ?? null,
                    $client['invoiceCity'] ?? null,
                    $client['invoiceZipCode'] ?? null,
                    $client['invoiceState'] ?? null,
                    $client['invoiceCountry'] ?? null,
                ];

                $client['invoiceAddress'] = implode(', ', array_filter($invoiceAddress));
                $client['invoiceAddress'] = $client['invoiceAddress'] ?: null;

                $client['invoiceAddressSameAsContact'] = $client['invoiceAddress'] === null
                    || $client['address'] === $client['invoiceAddress'];

                $client['username'] = $client['username'] ?? null;

                if ($client['nameForView'] ?? null) {
                    $fullName = explode(' ', $client['nameForView'], 2);
                    $client['firstName'] = $fullName[0] ?? null;
                    $client['lastName'] = $fullName[1] ?? null;
                }
                $client['fullName'] = implode(
                    ' ',
                    array_filter([$client['firstName'] ?? null, $client['lastName'] ?? null])
                );
                $client['fullName'] = $client['fullName'] ?: null;

                if (isset($client['emails'])) {
                    $emails = explode(',', $client['emails']);
                    $i = 4;
                    foreach ($emails as $email) {
                        $client['email' . $i] = trim($email);
                        ++$i;
                    }
                    unset($client['emails']);
                }

                if (isset($client['phones'])) {
                    $phones = explode(',', $client['phones']);
                    $i = 4;
                    foreach ($phones as $phone) {
                        $client['phone' . $i] = $phone;
                        ++$i;
                    }
                    unset($client['phones']);
                }

                $client['rowNumber'] = $rowNumber;
                $clients[] = $client;
            }

            ++$rowNumber;
        }

        $clients = \AppBundle\Util\Strings::fixEncodingRecursive($clients);

        return $this->validateClients($clients);
    }

    /**
     * Processes actual payments import (saves data to database) and returns count of saved payments.
     */
    public function processPaymentsImport(array $payments): int
    {
        $this->paymentFacade->handleCreateMultipleWithoutInvoiceIds($payments);

        return count($payments);
    }

    /**
     * Enqueues client import to RabbitMQ and returns count of unique clients, that will be imported.
     * Processes actual client import (saves data to database) and returns count of saved clients.
     */
    public function enqueuePaymentsImport(array $payments, User $user): int
    {
        if (! $this->entityManager->contains($user)) {
            $user = $this->entityManager->find(User::class, $user->getId());
        }

        $count = count($payments);
        $csvImport = new CsvImport();
        $csvImport->setCount($count);
        $csvImport->setUser($user);
        $csvImport->setType(CsvImport::TYPE_PAYMENT);
        $this->csvImportFacade->handleNew($csvImport);

        foreach ($payments as $payment) {
            $this->rabbitMqEnqueuer->enqueue(
                new PaymentImportMessage(
                    $payment,
                    $csvImport->getUuid()
                )
            );
        }

        return $count;
    }

    /**
     * Returns array of filled payment rows from CSV.
     */
    public function getPayments(): array
    {
        $payments = [];

        $csvControl = $this->csvImporter->getCsvControl();
        $file = $this->file->openFile();
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(
            $csvControl[$this->csvImporter::FIELD_DELIMITER],
            $csvControl[$this->csvImporter::FIELD_ENCLOSURE],
            $csvControl[$this->csvImporter::FIELD_ESCAPE]
        );

        $map = $this->fieldsMap;
        $map = array_filter(
            $map,
            function ($value) {
                return $value !== '';
            }
        );

        $skippedHeader = false;
        foreach ($file as $row) {
            if ($row === false) {
                continue;
            }

            if ($this->skipHeader && ! $skippedHeader) {
                $skippedHeader = true;
                continue;
            }
            $payment = [];

            foreach ($map as $field => $column) {
                $payment[$field] = $row[$column] ?? '';
            }

            if (count($payment) > 0) {
                $payments[] = $payment;
            }
        }

        $payments = \AppBundle\Util\Strings::fixEncodingRecursive($payments);

        return $this->validatePayments($payments);
    }

    public function getRawCsvRows(): array
    {
        $csvControl = $this->csvImporter->getCsvControl();
        $file = $this->file->openFile();
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(
            $csvControl[$this->csvImporter::FIELD_DELIMITER],
            $csvControl[$this->csvImporter::FIELD_ENCLOSURE],
            $csvControl[$this->csvImporter::FIELD_ESCAPE]
        );

        $columns = [];
        $rows = [];
        $skippedHeader = false;
        foreach ($file as $row) {
            if ($row === false) {
                continue;
            }

            if (! $columns) {
                $columns = $row;
            }

            if ($this->skipHeader && ! $skippedHeader) {
                $skippedHeader = true;
                continue;
            }

            $fillMissing = count($columns) - count($row);
            if ($fillMissing > 0) {
                $row = array_merge($row, array_fill(count($row), $fillMissing, null));
            }
            if ($fillMissing < 0) {
                $columns = array_merge($columns, array_fill(count($columns), -$fillMissing, null));
            }

            $rows[] = array_combine($columns, $row);
        }

        return $rows;
    }

    /**
     * @return Payment[]
     */
    public function getPopulatedPayments(array $payments, ?User $user): array
    {
        return $this->populatePayments($payments, $user);
    }

    public function getMissingServicePlans(): array
    {
        return $this->missingPlans;
    }

    public function getMissingTaxes(): array
    {
        return $this->missingTaxes;
    }

    /**
     * Returns array of clients with '_error' key populated with array of errors.
     */
    private function validateClients(array $clients): array
    {
        $cacheItemPool = new ApcuAdapter('CustomCsvImport_validateClients', 60);
        $builder = PropertyAccess::createPropertyAccessorBuilder();
        $builder->setCacheItemPool($cacheItemPool);
        $propertyAccessor = $builder->getPropertyAccessor();

        $entityValidator = new EntityValidator($this->validator, $propertyAccessor);

        $userIdents = [];
        $minDate = DateTimeFactory::createDate('1900-01-01');

        $prevClient = null;
        $prevClientRowId = null;
        /** @var array $client */
        foreach ($clients as $clientRowId => &$client) {
            $errors = [];
            $client['_prevClient'] = false;

            $emptyClient = true;
            $serviceData = [];
            $ignoredColumnsWhenCheckingIfClientIsEmpty = [
                'address',
                'invoiceAddress',
                'fullName',
                'invoiceAddressSameAsContact',
                'rowNumber',
                '_prevClient',
            ];
            foreach ($client as $item => $value) {
                if (Strings::startsWith($item, 'service')) {
                    $serviceData[$item] = $value;
                } elseif (
                    ! in_array($item, $ignoredColumnsWhenCheckingIfClientIsEmpty, true)
                    && ! empty($value)
                ) {
                    $emptyClient = false;
                }
            }
            if ($emptyClient && $prevClient) {
                // clean service data from previous client, each service row is unique
                $duplicateClient = $prevClient;
                foreach ($duplicateClient as $item => $value) {
                    if (Strings::startsWith($item, 'service')) {
                        unset($duplicateClient[$item]);
                    }
                }

                $client = array_replace($duplicateClient, $serviceData);
                $client['_prevClient'] = $prevClientRowId;

                // if prevClient's custom ID already exists, this one's has to exist as well
                if (isset($prevClient['_errors']['userIdent'])) {
                    $errors['userIdent'] = $prevClient['_errors']['userIdent'];
                }
            }

            // check for already existing custom ID, prevClient's is handled above
            if (($client['_prevClient'] ?? false) === false && isset($client['userIdent'])) {
                // @TODO: check for userIdent in database?
                if (
                    ! $this->clientRepository->isUserIdentUnique($client['userIdent'])
                    || in_array($client['userIdent'], $userIdents, true)
                ) {
                    $errors['userIdent'] = $this->translator->trans('Client with this custom ID already exists.');
                } else {
                    $userIdents[] = $client['userIdent'];
                }
            }

            if (! isset($client['firstName']) && ! isset($client['companyName'])) {
                $errors['name'] = $this->translator->trans('Name is required.');
            }

            if (! isset($client['lastName']) && ! isset($client['companyName'])) {
                $errors['lastName'] = $this->translator->trans('Last name is required.');
            }

            if (isset($client['registrationDate'])) {
                $registrationDate = $this->clientCsvImportHandler->tryParseDateTime($client['registrationDate']);
                if ($registrationDate) {
                    if ($registrationDate < $minDate) {
                        $errors['registrationDate'] = $this->translator->trans('Date must be after 1900-01-01.');
                    }
                } else {
                    $errors['createdDate'] = $this->translator->trans('Date is not in valid format.');
                }
            }

            $country = null;
            if (isset($client['country'])) {
                $country = $this->clientCsvImportHandler->getCountryByName($client['country']);
                if (! $country) {
                    $errors['country'] = $this->translator->trans('Country not found.');
                }
            }

            $invoiceCountry = null;
            if (isset($client['invoiceCountry'])) {
                $invoiceCountry = $this->clientCsvImportHandler->getCountryByName($client['invoiceCountry']);
                if (! $invoiceCountry) {
                    $errors['invoiceCountry'] = $this->translator->trans('Invoice country not found.');
                }
            }

            $state = null;
            if (isset($client['state'])) {
                $state = $this->clientCsvImportHandler->getStateByCode($client['state']);
                $state = $state ?? $this->clientCsvImportHandler->getStateByName($client['state']);
                if (! $state) {
                    $errors['state'] = $this->translator->trans('State not found.');
                } elseif ($country && $state->getCountry() !== $country) {
                    $errors['state'] = $this->translator->trans('State does not belong to this country.');
                }
            }

            if ($country && ! $state && ! $country->getStates()->isEmpty()) {
                $errors['state'] = $this->translator->trans('State is required for this country.');
            }

            $invoiceState = null;
            if (isset($client['invoiceState'])) {
                $invoiceState = $this->clientCsvImportHandler->getStateByCode($client['invoiceState']);
                $invoiceState = $invoiceState ?? $this->clientCsvImportHandler->getStateByName($client['invoiceState']);
                if (! $invoiceState) {
                    $errors['invoiceState'] = $this->translator->trans('Invoice state not found.');
                } elseif ($invoiceCountry && $invoiceState->getCountry() !== $invoiceCountry) {
                    $errors['invoiceState'] = $this->translator->trans(
                        'Invoice state does not belong to this invoice country.'
                    );
                }
            }

            if ($invoiceCountry && ! $invoiceCountry->getStates()->isEmpty() && ! $invoiceState) {
                $errors['invoiceState'] = $this->translator->trans(
                    'Invoice state is required for this invoice country.'
                );
            }

            if (isset($client['addressGpsLat']) && ! is_numeric($client['addressGpsLat'])) {
                $errors['addressGpsLat'] = $this->translator->trans(
                    'Client latitude should be a valid number.'
                );
            }

            if (isset($client['addressGpsLon']) && ! is_numeric($client['addressGpsLon'])) {
                $errors['addressGpsLon'] = $this->translator->trans(
                    'Client longitude should be a valid number.'
                );
            }

            foreach (self::KEYS_TAXES as $taxId) {
                if (! array_key_exists($taxId, $client)) {
                    continue;
                }

                $taxName = trim($client[$taxId] ?? '');
                if ($taxName === '') {
                    continue;
                }

                $tax = $this->clientCsvImportHandler->getTaxByName($taxName);
                if ($tax instanceof Tax) {
                    $taxEntityErrors = $this->validator->validate($tax);
                    foreach ($taxEntityErrors as $violation) {
                        $errors[$taxId] = $violation->getMessage();
                    }
                } else {
                    $errors[$taxId] = $this->translator->trans(
                        'Tax %tax% not found.',
                        ['%tax%' => $taxName]
                    );

                    if (! in_array($taxName, $this->missingTaxes, true)) {
                        $this->missingTaxes[] = $taxName;
                    }
                }
            }

            //defaults
            $client['role'] = User::ROLE_CLIENT;
            $client['clientType'] = empty($client['companyName']) ? Client::TYPE_RESIDENTIAL : Client::TYPE_COMPANY;

            $entityErrors = $entityValidator->validateEntityByProperties(
                $this->validator,
                Client::class,
                $client,
                ['CsvClient']
            );
            $entityErrors += $entityValidator->validateEntityByProperties(
                $this->validator,
                User::class,
                $client,
                ['CsvUser']
            );

            $contacts = $this->clientCsvImportHandler->getClientContacts($client);
            foreach ($contacts as $contact) {
                $entityErrors += $entityValidator->validateEntityByProperties(
                    $this->validator,
                    ClientContact::class,
                    $contact,
                    ['CsvClientContact']
                );
            }

            if (isset($client['servicePlan']) || isset($client['serviceTariffPeriod'])) {
                $serviceData = $this->clientCsvImportHandler->getServiceData($client, $this->organization);
                $serviceEntityErrors = $entityValidator->validateEntityByProperties(
                    $this->validator,
                    Service::class,
                    $serviceData
                );
                foreach (self::KEYS_TAXES as $taxId) {
                    if (array_key_exists($taxId, $serviceData)) {
                        if (is_a($serviceData[$taxId], Tax::class)) {
                            $taxEntityErrors = $this->validator->validate($serviceData[$taxId]);
                            foreach ($taxEntityErrors as $violation) {
                                $entityErrors['service' . Strings::firstUpper($taxId)] = $violation->getMessage();
                            }
                        } else {
                            $entityErrors['service' . Strings::firstUpper($taxId)] = $this->translator->trans(
                                'Tax %tax% not found.',
                                ['%tax%' => $serviceData[$taxId]]
                            );

                            if (! in_array($serviceData[$taxId], $this->missingTaxes, true)) {
                                $this->missingTaxes[] = $serviceData[$taxId];
                            }
                        }
                    }
                }
                foreach ($serviceEntityErrors as $property => $error) {
                    $entityErrors['service' . Strings::firstUpper($property)] = $error;
                }
                unset($entityErrors['serviceClient']);
                if (! ($serviceData['tariff'] ?? null) xor ! ($serviceData['tariffPeriod'] ?? null)) {
                    // if one defined, the other one also needs to be
                    $entityErrors['serviceTariffPeriod'] = $this->translator->trans(
                        'Both service plan and its period has to be entered.'
                    );
                }

                /** @var Tariff $tariff */
                $tariff = $serviceData['tariff'] ?? null;
                $monthsArray = [];

                // check for *enabled* periods only
                if (is_a($tariff, Tariff::class)) {
                    /** @var TariffPeriod $period */
                    foreach ($tariff->getEnabledPeriods() as $period) {
                        $monthsArray[] = $period->getPeriod();
                    }

                    /** @var TariffPeriod $tariffPeriod */
                    $tariffPeriod = $serviceData['tariffPeriod'];
                    if (
                        is_a($tariffPeriod, TariffPeriod::class)
                        && ! in_array($tariffPeriod->getPeriod(), $monthsArray, false)
                    ) {
                        $entityErrors['serviceTariffPeriod'] = true;
                    }
                }
                if (isset($entityErrors['serviceTariff']) && ($client['servicePlan'] ?? null)) {
                    $entityErrors['serviceTariff'] = $this->translator->trans(
                        'Service plan %plan% not found.',
                        ['%plan%' => $client['servicePlan']]
                    );
                    if (! in_array($client['servicePlan'], $this->missingPlans, true)) {
                        $this->missingPlans[] = $client['servicePlan'];
                    }
                    unset($entityErrors['serviceTariffPeriod']);
                } elseif (isset($entityErrors['serviceTariffPeriod'])) {
                    $months = implode(', ', $monthsArray);
                    $entityErrors['serviceTariffPeriod'] = $this->translator->trans(
                        'Service period should be a number of months: %months%.',
                        ['%months%' => $months]
                    );
                }

                if (isset($entityErrors['serviceContractLengthType'])) {
                    $entityErrors['serviceContractLengthType'] = $this->translator->trans(
                        'Contract type should be "open" or "closed".'
                    );
                }
                if (isset($entityErrors['serviceInvoicingPeriodType'])) {
                    $entityErrors['serviceInvoicingPeriodType'] = $this->translator->trans(
                        'Invoicing period type should be "forward" or "backward".'
                    );
                }

                if (isset($serviceData['addressGpsLat']) && ! is_numeric($serviceData['addressGpsLat'])) {
                    $entityErrors['serviceAddressGpsLat'] = $this->translator->trans(
                        'Service latitude should be a valid number.'
                    );
                }

                if (isset($serviceData['addressGpsLon']) && ! is_numeric($serviceData['addressGpsLon'])) {
                    $entityErrors['serviceAddressGpsLon'] = $this->translator->trans(
                        'Service longitude should be a valid number.'
                    );
                }
            }

            foreach ($entityErrors as $property => $error) {
                if (array_key_exists($property, self::LABELS_CLIENTS)) {
                    $property = str_replace('import/', '', self::LABELS_CLIENTS[$property]);
                }

                $errors[] = sprintf('%s: %s', $property, $error);
            }

            $client['_errors'] = $errors;
            if (($client['_prevClient'] ?? false) === false) {
                $prevClient = $client;
                $prevClientRowId = $clientRowId;
            }
        }

        return $clients;
    }

    /**
     * Returns array of payments with '_error' key populated with array of errors.
     */
    private function validatePayments(array $payments): array
    {
        $minDate = DateTimeFactory::createDate('1900-01-01');

        foreach ($payments as &$payment) {
            $errors = [];
            $errorSummary = [];
            $validatablePayment = $payment;

            if (Strings::startsWith($this->fieldsMap['method'] ?? '', CsvImportPaymentType::PREFIX_PAYMENT_METHOD)) {
                $validatablePayment['method'] = (int) Strings::after(
                    $this->fieldsMap['method'],
                    CsvImportPaymentType::PREFIX_PAYMENT_METHOD
                );
            } else {
                if (array_key_exists('method', $validatablePayment)) {
                    $validatablePayment['method'] = $this->guessPaymentMethod($payment['method']);
                    if (! $validatablePayment['method']) {
                        $errors['method'] = $this->translator->trans(
                            'Method "%value%" not found.',
                            [
                                '%value%' => $payment['method'],
                            ]
                        );
                        $errorSummary[$this->translator->trans('Method not found.')]
                            = ($errorSummary[$this->translator->trans('Method not found.')] ?? 0) + 1;
                    }
                } else {
                    $errors['method'] = $this->translator->trans('Method is required.');
                    $errorSummary[$errors['method']]
                        = ($errorSummary[$errors['method']] ?? 0) + 1;
                }
            }

            if (array_key_exists('amount', $validatablePayment)) {
                $validatablePayment['amount'] = Strings::replace($validatablePayment['amount'], '/,/', '.');
                $validatablePayment['amount'] = (float) $validatablePayment['amount'];
                if ($validatablePayment['amount'] <= 0) {
                    $errors['amount'] = $this->translator->trans('Amount must be greater than 0.');
                    $errorSummary[$errors['amount']] = ($errorSummary[$errors['amount']] ?? 0) + 1;
                }
            } else {
                $errors['amount'] = $this->translator->trans('Amount is required.');
                $errorSummary[$errors['amount']] = ($errorSummary[$errors['amount']] ?? 0) + 1;
            }

            if (Strings::startsWith($this->fieldsMap['currency'] ?? '', CsvImportPaymentType::PREFIX_CURRENCY)) {
                $validatablePayment['currency'] = $this->getCurrencyById(
                    (int) Strings::after($this->fieldsMap['currency'], CsvImportPaymentType::PREFIX_CURRENCY)
                );
            } else {
                if (array_key_exists('currency', $validatablePayment)) {
                    $validatablePayment['currency'] = $this->getCurrencyByCode($payment['currency']);
                    if (null === $validatablePayment['currency']) {
                        $errors['currency'] = $this->translator->trans(
                            'Currency "%value%" not found.',
                            [
                                '%value%' => $payment['currency'],
                            ]
                        );
                        $errorSummary[$this->translator->trans('Currency not found.')]
                            = ($errorSummary[$this->translator->trans('Currency not found.')] ?? 0) + 1;
                    }
                } else {
                    $errors['currency'] = $this->translator->trans('Currency is required.');
                    $errorSummary[$errors['currency']] = ($errorSummary[$errors['currency']] ?? 0) + 1;
                }
            }

            if (array_key_exists('createdDate', $validatablePayment)) {
                $createdDate = $this->clientCsvImportHandler->tryParseDateTime($validatablePayment['createdDate']);
                if ($createdDate) {
                    $validatablePayment['createdDate'] = $createdDate;
                } else {
                    $errors['createdDate'] = $this->translator->trans('Date is not in valid format.');
                    $errorSummary[$errors['createdDate']] = ($errorSummary[$errors['createdDate']] ?? 0) + 1;
                }

                if ($createdDate && $createdDate < $minDate) {
                    $errors['createdDate'] = $this->translator->trans('Date must be after 1900-01-01.');
                    $errorSummary[$errors['createdDate']] = ($errorSummary[$errors['createdDate']] ?? 0) + 1;
                }
            }

            if (
                array_key_exists('client', $validatablePayment)
                && $validatablePayment['currency'] instanceof Currency
            ) {
                $client = $this->getClientForPayment($validatablePayment['client'], $errors, $errorSummary);

                if ($client) {
                    $organization = $client->getOrganization();
                    if ($organization) {
                        $currency = $organization->getCurrency();
                        if ($currency && $currency !== $validatablePayment['currency']) {
                            $errors['currency'] = $this->translator->trans(
                                'Payment currency (%paymentCurrency%) does not match client\'s currency (%clientCurrency%).',
                                [
                                    '%paymentCurrency%' => $validatablePayment['currency']->getCode(),
                                    '%clientCurrency%' => $client->getCurrencyCode(),
                                ],
                                'validators'
                            );
                            $errorSummary[$errors['currency']] = ($errorSummary[$errors['currency']] ?? 0) + 1;
                        }
                    }
                }
            }

            $payment['_errors'] = $errors;
            $payment['_errorSummary'] = $errorSummary;
        }

        return $payments;
    }

    private function getClientForPayment(string $value, &$errors = [], &$errorSummary = []): ?Client
    {
        $matchBy = $this->fieldsMap['clientMatch'] ?? CustomCsvImport::CLIENT_MATCH_BY_ID;

        $client = null;
        switch ($matchBy) {
            case CustomCsvImport::CLIENT_MATCH_BY_ID:
                if ((! is_numeric($value)) || ((int) $value != $value)) {
                    $errors['client'] = $this->translator->trans('Client ID is not integer.');
                    $errorSummary[$errors['client']] = ($errorSummary[$errors['client']] ?? 0) + 1;

                    break;
                }

                if ((int) $value > 2147483647) {
                    $errors['client'] = $this->translator->trans(
                        'Client ID is out of supported range for integer.'
                    );
                    $errorSummary[$errors['client']] = ($errorSummary[$errors['client']] ?? 0) + 1;

                    break;
                }

                $client = $this->entityManager->getRepository(Client::class)->findOneBy(
                    [
                        'id' => (int) $value,
                        'deletedAt' => null,
                    ]
                );

                if (! $client) {
                    $errors['client'] = $this->translator->trans(
                        'Client with %param% "%value%" not found.',
                        [
                            '%param%' => $this->translator->trans('ID'),
                            '%value%' => $value,
                        ]
                    );
                    $errorSummary[$this->translator->trans('Client not found.')]
                        = ($errorSummary[$this->translator->trans('Client not found.')] ?? 0) + 1;
                }

                break;
            case CustomCsvImport::CLIENT_MATCH_BY_CUSTOM_ID:
                $client = $this->entityManager->getRepository(Client::class)->findOneBy(
                    [
                        'userIdent' => $value,
                        'deletedAt' => null,
                    ]
                );

                if (! $client) {
                    $errors['client'] = $this->translator->trans(
                        'Client with %param% "%value%" not found.',
                        [
                            '%param%' => $this->translator->trans('Custom ID'),
                            '%value%' => $value,
                        ]
                    );
                    $errorSummary[$this->translator->trans('Client not found.')]
                        = ($errorSummary[$this->translator->trans('Client not found.')] ?? 0) + 1;
                }

                break;
            default:
                $customAttribute = $this->clientCsvImportHandler->getCustomAttributeByKey($matchBy);
                if (! $customAttribute) {
                    $errors['client'] = $this->translator->trans(
                        'Custom attribute not found.'
                    );
                    $errorSummary[$errors['client']]
                        = ($errorSummary[$errors['client']] ?? 0) + 1;

                    break;
                }

                $clientAttributes = $this->entityManager->getRepository(ClientAttribute::class)->findBy(
                    [
                        'attribute' => $customAttribute,
                        'value' => $value,
                    ]
                );

                $client = null;
                if (count($clientAttributes) > 1) {
                    $errors['client'] = $this->translator->trans(
                        'Multiple clients with %param% "%value%" found.',
                        [
                            '%param%' => sprintf('"%s"', $customAttribute->getName()),
                            '%value%' => $value,
                        ]
                    );
                    $errorSummary[$this->translator->trans('Multiple clients found.')]
                        = ($errorSummary[$this->translator->trans('Multiple clients found.')] ?? 0) + 1;
                } elseif ($clientAttributes) {
                    /** @var ClientAttribute $clientAttribute */
                    $clientAttribute = reset($clientAttributes);
                    if (! $clientAttribute->getClient()->isDeleted()) {
                        $client = $clientAttribute->getClient();
                    }
                }

                if (! ($errors['client'] ?? false) && ! $client) {
                    $errors['client'] = $this->translator->trans(
                        'Client with %param% "%value%" not found.',
                        [
                            '%param%' => sprintf('"%s"', $customAttribute->getName()),
                            '%value%' => $value,
                        ]
                    );
                    $errorSummary[$this->translator->trans('Client not found.')]
                        = ($errorSummary[$this->translator->trans('Client not found.')] ?? 0) + 1;
                }

                break;
        }

        return $client;
    }

    /**
     * Returns array of Payment entities prepared for import.
     *
     * @return Payment[]
     */
    private function populatePayments(array $data, ?User $user): array
    {
        $entities = [];

        foreach ($data as $row) {
            $payment = new Payment();
            $payment->setUser($user);

            $payment->setCreatedDate($this->clientCsvImportHandler->tryParseDateTime($row['createdDate'] ?? 'now'));
            $amount = Strings::replace($row['amount'], '/,/', '.');
            $payment->setAmount((float) $amount);
            if (array_key_exists('client', $row)) {
                $client = $row['client'] ? $this->getClientForPayment($row['client']) : null;
                $payment->setClient($client);
                if ($client) {
                    // we don't want to
                    $client->removePayment($payment);
                }
            }
            $payment->setNote($row['note'] ?? null);

            if (Strings::startsWith($this->fieldsMap['currency'] ?? '', CsvImportPaymentType::PREFIX_CURRENCY)) {
                $payment->setCurrency(
                    $this->getCurrencyById(
                        (int) Strings::after($this->fieldsMap['currency'], CsvImportPaymentType::PREFIX_CURRENCY)
                    )
                );
            } elseif ($row['currency'] ?? false) {
                $payment->setCurrency($this->getCurrencyByCode($row['currency']));
            }

            if (Strings::startsWith($this->fieldsMap['method'] ?? '', CsvImportPaymentType::PREFIX_PAYMENT_METHOD)) {
                $payment->setMethod(
                    (int) Strings::after($this->fieldsMap['method'], CsvImportPaymentType::PREFIX_PAYMENT_METHOD)
                );
            } else {
                $payment->setMethod($this->guessPaymentMethod($row['method'] ?? null));
            }

            $entities[] = $payment;
        }

        return $entities;
    }

    private function guessPaymentMethod(?string $method): ?int
    {
        if (null === $method) {
            return null;
        }
        $method = Strings::lower($method);

        switch ($method) {
            case 'check':
            case (string) Payment::METHOD_CHECK:
                return Payment::METHOD_CHECK;
                break;
            case 'cash':
            case (string) Payment::METHOD_CASH:
                return Payment::METHOD_CASH;
                break;
            case 'bank transfer':
            case 'bank':
            case (string) Payment::METHOD_BANK_TRANSFER:
                return Payment::METHOD_BANK_TRANSFER;
                break;
            case 'paypal':
            case (string) Payment::METHOD_PAYPAL:
                return Payment::METHOD_PAYPAL;
                break;
            case 'paypal credit card':
            case 'paypal card':
            case (string) Payment::METHOD_PAYPAL_CREDIT_CARD:
                return Payment::METHOD_PAYPAL_CREDIT_CARD;
                break;
            case 'stripe':
            case 'stripe credit card':
            case 'stripe card':
            case (string) Payment::METHOD_STRIPE:
                return Payment::METHOD_STRIPE;
                break;
            case 'stripe subscription':
            case 'stripe subscription (credit card)':
            case 'stripe subscription credit card':
            case (string) Payment::METHOD_STRIPE_SUBSCRIPTION:
                return Payment::METHOD_STRIPE_SUBSCRIPTION;
                break;
            case 'paypal subscription':
            case (string) Payment::METHOD_PAYPAL_SUBSCRIPTION:
                return Payment::METHOD_PAYPAL_SUBSCRIPTION;
                break;
            case 'anet':
            case 'authorize.net':
            case (string) Payment::METHOD_AUTHORIZE_NET:
                return Payment::METHOD_AUTHORIZE_NET;
                break;
            case 'anet subscription':
            case 'authorize.net subscription':
            case (string) Payment::METHOD_AUTHORIZE_NET_SUBSCRIPTION:
                return Payment::METHOD_AUTHORIZE_NET_SUBSCRIPTION;
                break;
            case 'courtesy':
            case 'courtesy credit':
            case (string) Payment::METHOD_COURTESY_CREDIT:
                return Payment::METHOD_COURTESY_CREDIT;
                break;
            case 'ippay':
            case (string) Payment::METHOD_IPPAY:
                return Payment::METHOD_IPPAY;
                break;
            case 'ippay subscription':
            case (string) Payment::METHOD_IPPAY_SUBSCRIPTION:
                return Payment::METHOD_IPPAY_SUBSCRIPTION;
                break;
            case 'mercadopago':
            case (string) Payment::METHOD_MERCADO_PAGO:
                return Payment::METHOD_MERCADO_PAGO;
                break;
            case 'mercadopago subscription':
            case (string) Payment::METHOD_MERCADO_PAGO_SUBSCRIPTION:
                return Payment::METHOD_MERCADO_PAGO_SUBSCRIPTION;
                break;
            case 'stripe ach':
            case (string) Payment::METHOD_STRIPE_ACH:
                return Payment::METHOD_STRIPE_ACH;
                break;
            case 'stripe subscription (ach)':
            case 'stripe subscription ach':
            case (string) Payment::METHOD_STRIPE_SUBSCRIPTION_ACH:
                return Payment::METHOD_STRIPE_SUBSCRIPTION_ACH;
                break;
            case 'custom':
            case (string) Payment::METHOD_CUSTOM:
                return Payment::METHOD_CUSTOM;
                break;
        }

        return null;
    }

    private function getCurrencyByCode(string $currencyCode): ?Currency
    {
        $currencyCode = Strings::upper($currencyCode);

        if (empty($this->currenciesByCode)) {
            $this->initCurrencyMappingTables();
        }

        return array_key_exists($currencyCode, $this->currenciesByCode) ? $this->currenciesByCode[$currencyCode] : null;
    }

    private function getCurrencyById(int $currencyId): ?Currency
    {
        if (empty($this->currenciesById)) {
            $this->initCurrencyMappingTables();
        }

        return array_key_exists($currencyId, $this->currenciesById) ? $this->currenciesById[$currencyId] : null;
    }

    private function initCurrencyMappingTables(): void
    {
        $currencies = $this->entityManager->getRepository(Currency::class)->findAll();
        foreach ($currencies as $currency) {
            $this->currenciesById[$currency->getId()] = $currency;
            $this->currenciesByCode[$currency->getCode()] = $currency;
        }
    }
}
