<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

use AppBundle\Component\AccountStatement\AccountStatement;
use AppBundle\DataProvider\DummyAccountStatementDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\ContactType;
use AppBundle\Entity\Country;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\OrganizationBankAccount;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentToken;
use AppBundle\Entity\Refund;
use AppBundle\Entity\Service;
use AppBundle\Entity\State;
use AppBundle\Factory\Financial\FinancialFactory;
use AppBundle\Service\Client\ClientAccountStatementCalculator;
use AppBundle\Service\InvoiceCalculations;
use AppBundle\Service\Options;
use AppBundle\Service\Payment\PaymentCoversGenerator;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;

/**
 * Used to generate an invoice or a quote with dummy (random) data.
 * The dummy invoice / quote is then used for template preview and validation.
 */
class DummyFinancialFactory
{
    private const DUMMY_LOGO_FILE = 'dummy_logo.png';
    private const DUMMY_STAMP_FILE = 'dummy_stamp.png';

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
     * @var FinancialFactory
     */
    private $financialFactory;

    /**
     * @var FinancialTotalCalculator
     */
    private $financialTotalCalculator;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var DummyFinancialItemFactory
     */
    private $itemFactory;

    /**
     * @var PaymentCoversGenerator
     */
    private $paymentCoversGenerator;

    /**
     * @var InvoiceCalculations
     */
    private $invoiceCalculations;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var DummyAccountStatementDataProvider
     */
    private $dummyAccountStatementDataProvider;

    /**
     * @var ClientAccountStatementCalculator
     */
    private $accountStatementCalculator;

    /**
     * @var InvoiceTaxableSupplyDateCalculator
     */
    private $invoiceTaxableSupplyDateCalculator;

    public function __construct(
        string $imgDir,
        EntityManagerInterface $entityManager,
        FinancialFactory $financialFactory,
        FinancialTotalCalculator $financialTotalCalculator,
        Options $options,
        DummyFinancialItemFactory $itemFactory,
        PaymentCoversGenerator $paymentCoversGenerator,
        InvoiceCalculations $invoiceCalculations,
        Formatter $formatter,
        DummyAccountStatementDataProvider $dummyAccountStatementDataProvider,
        ClientAccountStatementCalculator $accountStatementCalculator,
        InvoiceTaxableSupplyDateCalculator $invoiceTaxableSupplyDateCalculator
    ) {
        $this->imgDir = $imgDir;
        $this->entityManager = $entityManager;
        $this->faker = Factory::create();
        $this->faker->seed();
        $this->financialFactory = $financialFactory;
        $this->financialTotalCalculator = $financialTotalCalculator;
        $this->options = $options;
        $this->itemFactory = $itemFactory;
        $this->paymentCoversGenerator = $paymentCoversGenerator;
        $this->invoiceCalculations = $invoiceCalculations;
        $this->formatter = $formatter;
        $this->dummyAccountStatementDataProvider = $dummyAccountStatementDataProvider;
        $this->accountStatementCalculator = $accountStatementCalculator;
        $this->invoiceTaxableSupplyDateCalculator = $invoiceTaxableSupplyDateCalculator;
    }

    public function createInvoice(?Client $client = null): Invoice
    {
        if (! $client) {
            $organization = $this->createOrganization();
            $this->fillNonRequiredOrganizationData($organization);
            $client = $this->createClient($organization);
            $this->fillNonRequiredClientData($client);
        }

        $invoice = $this->financialFactory->createInvoice($client, new \DateTimeImmutable());
        $this->fillInvoiceDates($invoice);
        $invoice->setInvoiceStatus(Invoice::PARTIAL);
        $invoice->setInvoiceNumber($this->faker->numerify('PRW##########'));
        $invoice->setTemplateIncludeBankAccount(true);
        $invoice->setTemplateIncludeTaxInformation(true);
        $invoice->setPricingMode($this->options->get(Option::PRICING_MODE));

        $this->fillItems($invoice);

        $invoice->setNotes(sprintf('Invoice notes: %s', $this->faker->text()));
        $invoice->setDiscountInvoiceLabel('Discount');
        $invoice->setDiscountType(FinancialInterface::DISCOUNT_PERCENTAGE);
        $invoice->setDiscountValue((float) $this->faker->numberBetween(1, 99));

        $this->financialTotalCalculator->computeTotal($invoice);
        $amountPaid = (float) $this->faker->numberBetween(0, $invoice->getAmountToPay());
        if ($amountPaid > 0) {
            $payment = new Payment();
            $payment->setAmount($amountPaid);
            $this->paymentCoversGenerator->processPayment($payment, [$invoice]);
            $this->invoiceCalculations->recalculatePayments($invoice);
        }

        $token = new PaymentToken();
        $token->setInvoice($invoice);
        $token->generateToken();
        $invoice->setPaymentToken($token);

        return $invoice;
    }

    public function createInvoiceRequiredOnly(): Invoice
    {
        $organization = $this->createOrganization();
        $client = $this->createClient($organization);
        $this->fillNonRequiredClientData($client);
        $invoice = $this->financialFactory->createInvoice($client, new \DateTimeImmutable());
        $this->fillInvoiceDates($invoice);

        $invoice->setInvoiceStatus(Invoice::PARTIAL);
        $invoice->setInvoiceNumber($this->faker->numerify('PRW##########'));
        $invoice->setTemplateIncludeBankAccount(true);
        $invoice->setTemplateIncludeTaxInformation(true);
        $invoice->setPricingMode($this->options->get(Option::PRICING_MODE));

        $itemOther = $this->itemFactory->createInvoiceItemOther();
        $itemOther->setTaxable(false);
        $itemOther->setTax1(null);
        $invoice->addInvoiceItem($itemOther);

        $this->financialTotalCalculator->computeTotal($invoice);
        $amountPaid = (float) $this->faker->numberBetween(0, $invoice->getAmountToPay());
        if ($amountPaid > 0) {
            $payment = new Payment();
            $payment->setAmount($amountPaid);
            $this->paymentCoversGenerator->processPayment($payment, [$invoice]);
            $this->invoiceCalculations->recalculatePayments($invoice);
        }

        $token = new PaymentToken();
        $token->setInvoice($invoice);
        $token->generateToken();
        $invoice->setPaymentToken($token);

        return $invoice;
    }

    public function createProformaInvoice(?Client $client = null): Invoice
    {
        if (! $client) {
            $organization = $this->createOrganization();
            $this->fillNonRequiredOrganizationData($organization);
            $client = $this->createClient($organization);
            $this->fillNonRequiredClientData($client);
        }

        $invoice = $this->financialFactory->createInvoice($client, new \DateTimeImmutable());
        $this->fillInvoiceDates($invoice);
        $invoice->setInvoiceStatus(Invoice::PARTIAL);
        $invoice->setInvoiceNumber($this->faker->numerify('PRW##########'));
        $invoice->setTemplateIncludeBankAccount(true);
        $invoice->setTemplateIncludeTaxInformation(true);
        $invoice->setPricingMode($this->options->get(Option::PRICING_MODE));

        $this->fillItems($invoice);

        $invoice->setNotes(sprintf('Invoice notes: %s', $this->faker->text()));
        $invoice->setDiscountInvoiceLabel('Discount');
        $invoice->setDiscountType(FinancialInterface::DISCOUNT_PERCENTAGE);
        $invoice->setDiscountValue((float) $this->faker->numberBetween(1, 99));

        $this->financialTotalCalculator->computeTotal($invoice);
        $amountPaid = (float) $this->faker->numberBetween(0, $invoice->getAmountToPay());
        if ($amountPaid > 0) {
            $payment = new Payment();
            $payment->setAmount($amountPaid);
            $this->paymentCoversGenerator->processPayment($payment, [$invoice]);
            $this->invoiceCalculations->recalculatePayments($invoice);
        }

        $token = new PaymentToken();
        $token->setInvoice($invoice);
        $token->generateToken();
        $invoice->setPaymentToken($token);

        return $invoice;
    }

    public function createProformaInvoiceRequiredOnly(): Invoice
    {
        $organization = $this->createOrganization();
        $client = $this->createClient($organization);
        $this->fillNonRequiredClientData($client);
        $invoice = $this->financialFactory->createInvoice($client, new \DateTimeImmutable());
        $this->fillInvoiceDates($invoice);

        $invoice->setInvoiceStatus(Invoice::PARTIAL);
        $invoice->setInvoiceNumber($this->faker->numerify('PRW##########'));
        $invoice->setTemplateIncludeBankAccount(true);
        $invoice->setTemplateIncludeTaxInformation(true);
        $invoice->setPricingMode($this->options->get(Option::PRICING_MODE));

        $itemOther = $this->itemFactory->createInvoiceItemOther();
        $itemOther->setTaxable(false);
        $itemOther->setTax1(null);
        $invoice->addInvoiceItem($itemOther);

        $this->financialTotalCalculator->computeTotal($invoice);
        $amountPaid = (float) $this->faker->numberBetween(0, $invoice->getAmountToPay());
        if ($amountPaid > 0) {
            $payment = new Payment();
            $payment->setAmount($amountPaid);
            $this->paymentCoversGenerator->processPayment($payment, [$invoice]);
            $this->invoiceCalculations->recalculatePayments($invoice);
        }

        $token = new PaymentToken();
        $token->setInvoice($invoice);
        $token->generateToken();
        $invoice->setPaymentToken($token);

        return $invoice;
    }

    public function createRefund(?Client $client = null): Refund
    {
        if (! $client) {
            $organization = $this->createOrganization();
            $this->fillNonRequiredOrganizationData($organization);
            $client = $this->createClient($organization);
            $this->fillNonRequiredClientData($client);
        }

        $refund = new Refund();
        $refund->setMethod($this->faker->randomElement(Refund::POSSIBLE_METHODS));
        $refund->setAmount((float) $this->faker->numberBetween(0, 10));
        $refund->setClient($client);
        $refund->setCurrency($client->getOrganization()->getCurrency());
        $refund->setCreatedDate($this->faker->dateTimeBetween('-6 month', 'today'));
        $refund->setNote($this->faker->realText(30));

        return $refund;
    }

    public function createPayment(?Client $client = null): Payment
    {
        if (! $client) {
            $organization = $this->createOrganization();
            $this->fillNonRequiredOrganizationData($organization);
            $client = $this->createClient($organization);
            $this->fillNonRequiredClientData($client);
        }

        $payment = new Payment();
        $payment->setClient($client);
        $payment->setCurrency($client->getOrganization()->getCurrency());
        $payment->setMethod($this->faker->randomElement(Payment::POSSIBLE_METHODS));
        $payment->setNote($this->faker->realText(10));
        $payment->setCreatedDate($this->faker->dateTimeBetween('-6 month', 'today'));
        $payment->setAmount((float) $this->faker->numberBetween(0, 50));

        return $payment;
    }

    public function createQuote(): Quote
    {
        $organization = $this->createOrganization();
        $this->fillNonRequiredOrganizationData($organization);
        $client = $this->createClient($organization);
        $this->fillNonRequiredClientData($client);

        $quote = $this->financialFactory->createQuote($client, new \DateTimeImmutable());
        $quote->setQuoteNumber($this->faker->numerify('PRW##########'));
        $quote->setCreatedDate($this->faker->dateTimeBetween('-1 year', 'today'));
        $quote->setTemplateIncludeBankAccount(true);
        $quote->setTemplateIncludeTaxInformation(true);
        $quote->setPricingMode($this->options->get(Option::PRICING_MODE));

        $this->fillItems($quote);

        $quote->setNotes(sprintf('Invoice notes: %s', $this->faker->text()));
        $quote->setDiscountInvoiceLabel('Discount');
        $quote->setDiscountType(FinancialInterface::DISCOUNT_PERCENTAGE);
        $quote->setDiscountValue((float) $this->faker->numberBetween(1, 99));

        $this->financialTotalCalculator->computeTotal($quote);

        return $quote;
    }

    public function createQuoteRequiredOnly(): Quote
    {
        $organization = $this->createOrganization();
        $client = $this->createClient($organization);
        $this->fillNonRequiredClientData($client);
        $quote = $this->financialFactory->createQuote($client, new \DateTimeImmutable());
        $quote->setCreatedDate($this->faker->dateTimeBetween('-1 year', 'today'));
        $quote->setTemplateIncludeBankAccount(true);
        $quote->setTemplateIncludeTaxInformation(true);
        $quote->setPricingMode($this->options->get(Option::PRICING_MODE));

        $itemOther = $this->itemFactory->createQuoteItemOther();
        $itemOther->setTaxable(false);
        $itemOther->setTax1(null);
        $quote->addItem($itemOther);

        $this->financialTotalCalculator->computeTotal($quote);

        return $quote;
    }

    public function createAccountStatement(): AccountStatement
    {
        $organization = $this->createOrganization();
        $this->fillNonRequiredOrganizationData($organization);
        $client = $this->createClient($organization);
        $this->fillNonRequiredClientData($client);

        $invoices = [$this->createInvoice($client), $this->createInvoice($client), $this->createInvoice($client)];
        $payments = [$this->createPayment($client), $this->createPayment($client), $this->createPayment($client)];
        $refunds = [$this->createRefund($client)];

        $accountStatement = $this->dummyAccountStatementDataProvider->getAccountStatement(
            $client,
            $this->faker->dateTimeBetween('-1 year', '-6 months'),
            $this->faker->dateTimeBetween('-3 months', 'today'),
            (float) $this->faker->numberBetween(0, 50),
            $invoices,
            $payments,
            $refunds
        );

        $this->accountStatementCalculator->calculateBalances($accountStatement);

        return $accountStatement;
    }

    private function fillInvoiceDates(Invoice $invoice): void
    {
        $invoice->setCreatedDate($this->faker->dateTimeBetween('-1 year', 'today'));
        $dueDate = clone $invoice->getCreatedDate();
        $dueDate->modify(
            sprintf(
                '+%d days',
                $this->faker->numberBetween(1, 365)
            )
        );
        $invoice->setDueDate($dueDate);
        $invoice->setTaxableSupplyDate($this->invoiceTaxableSupplyDateCalculator->computeTaxableSupplyDate($invoice));
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

        $organization->setStripeLivePublishableKey('StripeLivePublishableKey');
        $organization->setStripeLiveSecretKey('StripeLiveSecretKey');
        $organization->setStripeTestPublishableKey('StripeTestPublishableKey');
        $organization->setStripeTestSecretKey('StripeTestSecretKey');
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
        $organization->addClient($client);

        return $client;
    }

    private function createContact(): ClientContact
    {
        $clientContact = new ClientContact();
        $clientContact->setEmail($this->faker->email);
        $clientContact->setPhone($this->faker->phoneNumber);
        $contactType = $this->entityManager->find(ContactType::class, 1);
        $this->entityManager->detach($contactType);
        $clientContact->addType($contactType);

        return $clientContact;
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

    private function fillItems(FinancialInterface $financial): void
    {
        if ($financial instanceof Invoice) {
            $serviceItem1 = $this->itemFactory->createInvoiceItemService(Service::DISCOUNT_FIXED);
            $financial->addItem($serviceItem1);
            $financial->addItem($this->itemFactory->createInvoiceItemSurcharge($serviceItem1->getService()));
            $financial->addItem($this->itemFactory->createInvoiceItemProduct());
            $financial->addItem($this->itemFactory->createInvoiceItemOther());
            $financial->addItem($this->itemFactory->createInvoiceItemFee());
        } elseif ($financial instanceof Quote) {
            $serviceItem1 = $this->itemFactory->createQuoteItemService(Service::DISCOUNT_FIXED);
            $financial->addItem($serviceItem1);
            $financial->addItem($this->itemFactory->createQuoteItemSurcharge($serviceItem1->getService()));
            $financial->addItem($this->itemFactory->createQuoteItemProduct());
            $financial->addItem($this->itemFactory->createQuoteItemOther());
            $financial->addItem($this->itemFactory->createQuoteItemFee());
        }
    }
}
