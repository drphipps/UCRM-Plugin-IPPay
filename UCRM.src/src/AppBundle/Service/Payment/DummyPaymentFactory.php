<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Payment;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\ContactType;
use AppBundle\Entity\Country;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Organization;
use AppBundle\Entity\OrganizationBankAccount;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentPayPal;
use AppBundle\Entity\State;
use AppBundle\Service\Financial\DummyFinancialFactory;
use AppBundle\Service\InvoiceCalculations;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;

/**
 * Used to generate a payment with dummy (random) data.
 * The dummy payment is then used for template preview and validation.
 */
class DummyPaymentFactory
{
    public const DUMMY_LOGO_FILE = 'dummy_logo.png';
    public const DUMMY_STAMP_FILE = 'dummy_stamp.png';

    /**
     * @var string
     */
    private $imgDir;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Generator
     */
    private $faker;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var DummyFinancialFactory
     */
    private $dummyFinancialFactory;

    /**
     * @var PaymentCoversGenerator
     */
    private $paymentCoversGenerator;

    /**
     * @var InvoiceCalculations
     */
    private $invoiceCalculations;

    public function __construct(
        string $imgDir,
        EntityManagerInterface $entityManager,
        Options $options,
        DummyFinancialFactory $dummyFinancialFactory,
        PaymentCoversGenerator $paymentCoversGenerator,
        InvoiceCalculations $invoiceCalculations
    ) {
        $this->imgDir = $imgDir;
        $this->entityManager = $entityManager;
        $this->faker = Factory::create();
        $this->faker->seed();
        $this->options = $options;
        $this->dummyFinancialFactory = $dummyFinancialFactory;
        $this->paymentCoversGenerator = $paymentCoversGenerator;
        $this->invoiceCalculations = $invoiceCalculations;
    }

    public function createPayment(): Payment
    {
        $organization = $this->createOrganization();
        $this->fillNonRequiredOrganizationData($organization);
        $client = $this->createClient($organization);
        $this->fillNonRequiredClientData($client);

        $invoice1 = $this->dummyFinancialFactory->createInvoice();
        $invoice2 = $this->dummyFinancialFactory->createInvoice();
        $invoice3 = $this->dummyFinancialFactory->createInvoice();

        $payment = new Payment();
        $payment->setId($this->faker->randomNumber());
        $payment->setClient($client);
        $payment->setAmount(
            $this->faker->randomFloat(
                2,
                $invoice1->getAmountToPay(),
                ($invoice1->getAmountToPay() + $invoice2->getAmountToPay() + $invoice3->getAmountToPay()) * 2
            )
        );
        $payment->setCurrency($organization->getCurrency());
        $payment->setCreatedDate(new \DateTime());
        $payment->setMethod(Payment::METHOD_CHECK);
        $payment->setCheckNumber($this->faker->numerify('CHCK#######'));
        $payment->setNote(implode(PHP_EOL, $this->faker->paragraphs()));
        $payment->setReceiptNumber($this->faker->numerify('PRW##########'));

        $paymentDetails = new PaymentPayPal();
        $paymentDetails->setPayPalId('72E4568WS3369585');
        $payment->setPaymentDetails($paymentDetails);

        $this->paymentCoversGenerator->processPayment(
            $payment,
            [
                $invoice1,
                $invoice2,
                $invoice3,
            ]
        );
        $this->invoiceCalculations->recalculatePayments($invoice1);
        $this->invoiceCalculations->recalculatePayments($invoice2);
        $this->invoiceCalculations->recalculatePayments($invoice3);

        return $payment;
    }

    public function createPaymentRequiredOnly(): Payment
    {
        $organization = $this->createOrganization();
        $client = $this->createClient($organization);

        $payment = new Payment();
        $payment->setClient($client);
        $payment->setAmount($this->faker->randomFloat(2, 1));
        $payment->setCurrency($organization->getCurrency());
        $payment->setCreatedDate(new \DateTime());
        $payment->setMethod(Payment::METHOD_CASH);

        return $payment;
    }

    private function createOrganization(): Organization
    {
        $organizationRepository = $this->entityManager->getRepository(Organization::class);
        $defaultOrganization = $organizationRepository->getFirstSelected();

        $organization = new Organization();
        $organization->setId($this->faker->randomNumber());
        $organization->setCurrency(
            $defaultOrganization
                ? $defaultOrganization->getCurrency()
                : $this->entityManager->find(Currency::class, Currency::DEFAULT_ID)
        );

        $organization->setName($this->faker->company);
        $organization->setStreet1($this->faker->streetAddress);
        $organization->setCity($this->faker->city);
        $organization->setCountry($this->entityManager->find(Country::class, 72));
        $organization->setZipCode($this->faker->postcode);
        $organization->setEmail($this->faker->companyEmail);

        return $organization;
    }

    private function fillNonRequiredOrganizationData(Organization $organization): void
    {
        $organization->setRegistrationNumber($this->faker->ean13);
        $organization->setTaxId($this->faker->ean13);

        $organization->setCountry($this->entityManager->find(Country::class, 249));
        $organization->setState($this->entityManager->find(State::class, 1));
        $organization->setStreet2($this->faker->buildingNumber);

        $bankAccount = new OrganizationBankAccount();
        $bankAccount->setName($this->faker->name);
        $bankAccount->setField1($this->faker->bankAccountNumber);
        $bankAccount->setField2($this->faker->bankAccountNumber);
        $organization->setBankAccount($bankAccount);

        $organization->setPhone($this->faker->phoneNumber);
        $organization->setWebsite($this->faker->url);

        $organization->setLogo(sprintf('%s/%s', $this->imgDir, self::DUMMY_LOGO_FILE));
        $organization->setStamp(sprintf('%s/%s', $this->imgDir, self::DUMMY_STAMP_FILE));
    }

    private function createClient(Organization $organization): Client
    {
        $client = new Client();
        $client->setUserIdent($this->faker->numerify('A###B#####Z'));
        $client->setOrganization($organization);
        $client->setClientType(Client::TYPE_RESIDENTIAL);
        $client->getUser()->setFirstName($this->faker->firstName);
        $client->getUser()->setLastName($this->faker->lastName);
        $client->setStreet1($this->faker->streetAddress);
        $client->setCountry($this->entityManager->find(Country::class, 72));
        $client->setCity($this->faker->city);
        $client->setZipCode($this->faker->postcode);
        $client->addContact($this->createContact());

        return $client;
    }

    private function createContact(): ClientContact
    {
        $clientContact = new ClientContact();
        $clientContact->setEmail($this->faker->email);
        $clientContact->setPhone($this->faker->phoneNumber);
        $clientContact->addType($this->createContactType());

        return $clientContact;
    }

    private function createContactType(): ContactType
    {
        $contactType = new ContactType();
        $contactType->setName('Billing');

        return $contactType;
    }

    private function fillNonRequiredClientData(Client $client): void
    {
        $client->setClientType(Client::TYPE_COMPANY);
        $client->setCompanyName($this->faker->company);
        $client->setCompanyRegistrationNumber($this->faker->ean13);
        $client->setCompanyTaxId($this->faker->ean13);
        $client->setCompanyWebsite($this->faker->url);

        $client->setStreet2($this->faker->buildingNumber);
        $client->setCountry($this->entityManager->find(Country::class, 249));
        $client->setState($this->entityManager->find(State::class, 1));

        $client->getUser()->setUsername($this->faker->userName);
        $client->setPreviousIsp($this->faker->company);
        $client->setNote($this->faker->paragraph());
        $client->setStopServiceDue(true);
        $client->setStopServiceDueDays(14);
        $client->setBalance(324.32);
        $client->setAccountStandingsCredit(124.24);
        $client->setAccountStandingsOutstanding(448.56);
        $client->setRegistrationDate($this->faker->dateTime);
        $client->setCompanyContactFirstName($this->faker->firstName);
        $client->setCompanyContactLastName($this->faker->lastName);
        $client->setHasSuspendedService(true);
        $client->setHasOutage(true);
        $client->setHasOverdueInvoice(true);
    }
}
