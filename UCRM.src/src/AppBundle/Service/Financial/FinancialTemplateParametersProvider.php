<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

use AppBundle\Component\AccountStatement\AccountStatement;
use AppBundle\Component\AccountStatement\AccountStatementItem;
use AppBundle\Component\Imagine\ImagineDataFileProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\FinancialItemFeeInterface;
use AppBundle\Entity\Financial\FinancialItemInterface;
use AppBundle\Entity\Financial\FinancialItemOtherInterface;
use AppBundle\Entity\Financial\FinancialItemProductInterface;
use AppBundle\Entity\Financial\FinancialItemServiceInterface;
use AppBundle\Entity\Financial\FinancialItemSurchargeInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItemService;
use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Service;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Facade\OnlinePaymentFacade;
use AppBundle\Factory\Financial\PaymentTokenFactory;
use AppBundle\Service\Client\ClientAccountStandingsCalculator;
use AppBundle\Service\Client\ClientBalanceFormatter;
use AppBundle\Service\Financial\TemplateData\ClientContact;
use AppBundle\Service\Financial\TemplateData\ContactType;
use AppBundle\Service\Financial\TemplateData\FinancialItem;
use AppBundle\Service\Financial\TemplateData\InvoiceTotals;
use AppBundle\Service\Financial\TemplateData\QuoteTotals;
use AppBundle\Service\Financial\TemplateData\TaxRecapitulation;
use AppBundle\Service\Financial\TemplateData\TaxTotal;
use AppBundle\Service\Financial\TemplateData\Totals;
use AppBundle\Service\InvoiceCalculations;
use AppBundle\Service\Options;
use AppBundle\Service\Payment\PaymentReceiptTemplateParametersProvider;
use AppBundle\Service\PublicUrlGenerator;
use AppBundle\Service\Tax\TaxCalculator;
use AppBundle\Util\Formatter;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\TranslatorInterface;

class FinancialTemplateParametersProvider
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ImagineDataFileProvider
     */
    private $imagineDataFileProvider;

    /**
     * @var Packages
     */
    private $packages;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    /**
     * @var InvoiceCalculations
     */
    private $invoiceCalculations;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var ClientAccountStandingsCalculator
     */
    private $clientAccountStandingsCalculator;

    /**
     * @var PaymentReceiptTemplateParametersProvider
     */
    private $paymentReceiptTemplateParametersProvider;

    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    /**
     * @var OnlinePaymentFacade
     */
    private $onlinePaymentFacade;

    /**
     * @var PaymentTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ClientBalanceFormatter
     */
    private $clientBalanceFormatter;

    public function __construct(
        Formatter $formatter,
        TranslatorInterface $translator,
        ImagineDataFileProvider $imagineDataFileProvider,
        Packages $packages,
        Filesystem $filesystem,
        TaxCalculator $taxCalculator,
        InvoiceCalculations $invoiceCalculations,
        Options $options,
        ClientAccountStandingsCalculator $clientAccountStandingsCalculator,
        PaymentReceiptTemplateParametersProvider $paymentReceiptTemplateParametersProvider,
        PublicUrlGenerator $publicUrlGenerator,
        OnlinePaymentFacade $onlinePaymentFacade,
        PaymentTokenFactory $paymentTokenFactory,
        EntityManagerInterface $entityManager,
        ClientBalanceFormatter $clientBalanceFormatter
    ) {
        $this->formatter = $formatter;
        $this->translator = $translator;
        $this->imagineDataFileProvider = $imagineDataFileProvider;
        $this->packages = $packages;
        $this->filesystem = $filesystem;
        $this->taxCalculator = $taxCalculator;
        $this->invoiceCalculations = $invoiceCalculations;
        $this->options = $options;
        $this->clientAccountStandingsCalculator = $clientAccountStandingsCalculator;
        $this->paymentReceiptTemplateParametersProvider = $paymentReceiptTemplateParametersProvider;
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->onlinePaymentFacade = $onlinePaymentFacade;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->entityManager = $entityManager;
        $this->clientBalanceFormatter = $clientBalanceFormatter;
    }

    public function getInvoiceParameters(Invoice $invoice, bool $includePotentialCredit = false): array
    {
        return array_merge(
            $this->getParametersInvoice($invoice),
            $this->getParametersClient($invoice),
            $this->getParametersOrganization($invoice),
            $this->getParametersItems($invoice),
            $this->getParametersInvoiceTotals($invoice, $includePotentialCredit),
            $this->getTaxRecapitulation($invoice)
        );
    }

    private function getInvoiceParametersOnly(Invoice $invoice): TemplateData\Invoice
    {
        return $this->getParametersInvoice($invoice)['invoice'];
    }

    public function getAccountStatementParameters(AccountStatement $accountStatement): array
    {
        return array_merge(
            $this->getParametersAccountStatement($accountStatement),
            $this->getParametersClientAccountStatement($accountStatement->client),
            $this->getParametersOrganizationAccountStatement($accountStatement->client->getOrganization())
        );
    }

    public function getQuoteParameters(Quote $quote): array
    {
        return array_merge(
            $this->getParametersQuote($quote),
            $this->getParametersClient($quote),
            $this->getParametersOrganization($quote),
            $this->getParametersItems($quote),
            $this->getParametersQuoteTotals($quote),
            $this->getTaxRecapitulation($quote)
        );
    }

    private function getParametersInvoice(Invoice $invoice): array
    {
        $data = new TemplateData\Invoice();
        $data->statusNumeric = $invoice->getInvoiceStatus();
        $data->status = $this->translator->trans(Invoice::STATUS_REPLACE_STRING[$data->statusNumeric]);
        $data->number = $invoice->getInvoiceNumber();
        $data->createdDate = $this->formatter->formatDate(
            $invoice->getCreatedDate(),
            Formatter::ALTERNATIVE,
            Formatter::NONE
        );
        $data->createdDateISO8601 = $invoice->getCreatedDate()
            ? $invoice->getCreatedDate()->format(\DateTime::ATOM)
            : (new \DateTime())->format(\DateTime::ATOM);
        $data->dueDate = $this->formatter->formatDate(
            $invoice->getDueDate(),
            Formatter::ALTERNATIVE,
            Formatter::NONE
        );
        $data->dueDateISO8601 = $invoice->getDueDate()
            ? $invoice->getDueDate()->format(\DateTime::ATOM)
            : (new \DateTime())->format(\DateTime::ATOM);
        $data->notes = $invoice->getNotes();
        $data->pricesWithTax = $invoice->getPricingMode() === Option::PRICING_MODE_WITH_TAXES;
        $data->taxableSupplyDate = $invoice->getTaxableSupplyDate()
            ? $this->formatter->formatDate(
                $invoice->getTaxableSupplyDate(),
                Formatter::ALTERNATIVE,
                Formatter::NONE
            )
            : '';
        $data->taxableSupplyDateISO8601 = $invoice->getTaxableSupplyDate()
            ? $invoice->getTaxableSupplyDate()->format(\DateTime::ATOM)
            : '';

        $attributes = [];
        foreach ($invoice->getAttributes() as $attribute) {
            $attributes[$attribute->getAttribute()->getKey()] = $attribute->getValue() ?? '';
        }
        $data->attributes = $attributes;

        $items = $invoice->getItems();
        foreach ($items as $item) {
            if ($item instanceof InvoiceItemService) {
                if (! $item->getService()) {
                    continue;
                }
                $data->firstServiceBillingPeriodType =
                    $item->getService()->getInvoicingPeriodType() === Service::INVOICING_BACKWARDS
                        ? 'backward'
                        : 'forward';

                break;
            }
        }

        try {
            $token = $invoice->getPaymentToken();
            if (! $token) {
                $token = $this->paymentTokenFactory->create($invoice);

                try {
                    $this->onlinePaymentFacade->handleCreatePaymentToken($token);
                } catch (UniqueConstraintViolationException $e) {
                    $this->entityManager->refresh($invoice);
                    $token = $invoice->getPaymentToken();
                }
            }
            $url = $this->publicUrlGenerator->generate(
                'online_payment_pay',
                [
                    'token' => $token->getToken(),
                ]
            );
        } catch (PublicUrlGeneratorException $e) {
            $url = null;
        }
        $data->onlinePaymentLink = $url;

        return [
            'invoice' => $data,
        ];
    }

    private function getParametersQuote(Quote $quote): array
    {
        $data = new TemplateData\Quote();
        $data->number = $quote->getQuoteNumber();
        $data->createdDate = $this->formatter->formatDate(
            $quote->getCreatedDate(),
            Formatter::ALTERNATIVE,
            Formatter::NONE
        );
        $data->createdDateISO8601 = $quote->getCreatedDate()
            ? $quote->getCreatedDate()->format(\DateTime::ATOM)
            : (new \DateTime())->format(\DateTime::ATOM);
        $data->notes = $quote->getNotes();
        $data->pricesWithTax = $quote->getPricingMode() === Option::PRICING_MODE_WITH_TAXES;

        $items = $quote->getItems();
        foreach ($items as $item) {
            if ($item instanceof QuoteItemService) {
                $data->firstServiceBillingPeriodType =
                    $item->getService()->getInvoicingPeriodType() === Service::INVOICING_BACKWARDS
                        ? 'backward'
                        : 'forward';

                break;
            }
        }

        return [
            'quote' => $data,
        ];
    }

    private function getParametersAccountStatement(AccountStatement $accountStatement): array
    {
        $data = new TemplateData\AccountStatement();
        $data->currency = $accountStatement->currency->getCode();
        $data->initialBalanceRaw = $accountStatement->initialBalance;
        $data->initialBalance = $this->formatter->formatCurrency(
            $data->initialBalanceRaw,
            $data->currency,
            $accountStatement->client ? $accountStatement->client->getOrganization()->getLocale() : null
        );
        $data->finalBalanceRaw = $accountStatement->finalBalance;
        $data->finalBalance = $this->formatter->formatCurrency(
            $data->finalBalanceRaw,
            $data->currency,
            $accountStatement->client ? $accountStatement->client->getOrganization()->getLocale() : null
        );
        $data->createdDate = $this->formatter->formatDate(
            $accountStatement->createdDate,
            Formatter::DEFAULT,
            Formatter::SHORT
        );
        $data->startDate = $accountStatement->startDate
            ? $this->formatter->formatDate(
                $accountStatement->startDate,
                Formatter::DEFAULT,
                Formatter::NONE
            )
            : null;
        $data->endDate = $accountStatement->endDate
            ? $this->formatter->formatDate(
                $accountStatement->endDate,
                Formatter::DEFAULT,
                Formatter::NONE
            )
            : null;
        $data->items = $this->getParametersAccountStatementItems($accountStatement->items, $accountStatement->client);

        return [
            'accountStatement' => $data,
        ];
    }

    /**
     * @param AccountStatementItem[] $items
     *
     * @return TemplateData\AccountStatementItem[]
     */
    private function getParametersAccountStatementItems(array $items, ?Client $client): array
    {
        $converted = [];
        foreach ($items as $item) {
            $data = new TemplateData\AccountStatementItem();
            $data->currency = $item->currency->getCode();
            $data->amountRaw = $item->amount;
            $data->amount = $this->formatter->formatCurrency(
                $data->amountRaw,
                $data->currency,
                $client ? $client->getOrganization()->getLocale() : null
            );
            $data->createdDate = $item->createdDate
                ? $this->formatter->formatDate(
                    $item->createdDate,
                    Formatter::DEFAULT,
                    Formatter::NONE
                )
                : null;
            $data->createdDateISO8601 = $item->createdDate
                ? $item->createdDate->format(\DateTime::ATOM)
                : null;
            $data->income = $item->income;
            $data->balanceRaw = $item->balance;
            $data->balance = $this->formatter->formatCurrency(
                $data->balanceRaw,
                $data->currency,
                $client ? $client->getOrganization()->getLocale() : null
            );
            $data->payment = $item->payment
                ? $this->paymentReceiptTemplateParametersProvider->getParametersOnlyPayment($item->payment)
                : null;
            $data->refund = $item->refund
                ? $this->paymentReceiptTemplateParametersProvider->getParametersRefund($item->refund)
                : null;
            $data->invoice = $item->invoice
                ? $this->getInvoiceParametersOnly($item->invoice)
                : null;

            $converted[] = $data;
        }

        return $converted;
    }

    public function getParametersClient(FinancialInterface $financial): array
    {
        $data = new TemplateData\Client();

        $data->name = $financial->getClientNameForView();
        $data->companyRegistrationNumber = $financial->getTemplateIncludeTaxInformation()
            ? $financial->getClientCompanyRegistrationNumber()
            : '';
        $data->companyTaxId = $financial->getTemplateIncludeTaxInformation()
            ? $financial->getClientCompanyTaxId()
            : '';

        $data->invoiceAddressSameAsContact = $financial->getClientInvoiceAddressSameAsContact();
        $data->street1 = $financial->getClientStreet1();
        $data->street2 = $financial->getClientStreet2();
        $data->city = $financial->getClientCity();
        $data->state = $financial->getClientState() ? $financial->getClientState()->getCode() : '';
        $data->zipCode = $financial->getClientZipCode();
        $data->country = $financial->getClientCountry() ? $financial->getClientCountry()->getName() : '';

        $data->invoiceStreet1 = $financial->getClientInvoiceStreet1();
        $data->invoiceStreet2 = $financial->getClientInvoiceStreet2();
        $data->invoiceCity = $financial->getClientInvoiceCity();
        $data->invoiceState = $financial->getClientInvoiceState() ?
            $financial->getClientInvoiceState()->getCode()
            : '';
        $data->invoiceZipCode = $financial->getClientInvoiceZipCode();
        $data->invoiceCountry = $financial->getClientInvoiceCountry() ?
            $financial->getClientInvoiceCountry()->getName()
            : '';
        $data->attributes = $financial->getClientAttributes();

        $client = $financial->getClient();
        if ($client) {
            $data->id = $client->getId();
            $data->userIdent = $client->getUserIdent();
            $data->type = $client->getClientType();
            $data->firstName = $client->getUser()->getFirstName();
            $data->lastName = $client->getUser()->getLastName();
            $data->username = $client->getUser()->getUsername();
            $data->companyName = $client->getCompanyName();
            $data->companyWebsite = $client->getCompanyWebsite();
            $data->companyContactFirstName = $client->getCompanyContactFirstName();
            $data->companyContactLastName = $client->getCompanyContactLastName();
            $data->suspendServicesIfPaymentIsOverdue = (bool) (
                $client->getStopServiceDue()
                ?? $this->options->get(Option::STOP_SERVICE_DUE)
            );
            $data->suspensionDelay = $client->getStopServiceDueDays()
                ?? $this->options->get(Option::STOP_SERVICE_DUE_DAYS);
            $data->previousIsp = $client->getPreviousIsp();
            $data->registrationDate = $client->getRegistrationDate()
                ? $this->formatter->formatDate(
                    $client->getRegistrationDate(),
                    Formatter::DEFAULT,
                    Formatter::NONE
                )
                : null;
            $data->note = $client->getNote();
            $data->hasSuspendedService = $client->hasSuspendedService();
            $data->hasOutage = $client->hasOutage();
            $data->hasOverdueInvoice = $client->hasOverdueInvoice();

            // force re-calculation to always include current invoice in these variables
            $this->clientAccountStandingsCalculator->calculate($client);
            $currency = $financial->getCurrency() ? $financial->getCurrency()->getCode() : $client->getCurrencyCode();
            $data->accountBalanceRaw = $this->clientBalanceFormatter->getFormattedBalanceRaw($client->getBalance());
            $data->accountBalance = $this->clientBalanceFormatter->getFormattedBalance($client, $currency);
            $data->accountCreditRaw = $client->getAccountStandingsCredit();
            $data->accountCredit = $this->formatter->formatCurrency(
                $data->accountCreditRaw,
                $currency,
                $this->getLocale($financial)
            );
            $data->accountOutstandingRaw = $client->getAccountStandingsOutstanding();
            $data->accountOutstanding = $this->formatter->formatCurrency(
                $data->accountOutstandingRaw,
                $currency,
                $this->getLocale($financial)
            );

            foreach ($client->getContacts() as $clientContact) {
                $data->contacts[] = $this->getClientContact($clientContact);
            }

            $data->firstBillingEmail = $client->getFirstBillingEmail();
            $data->firstBillingPhone = $client->getFirstBillingPhone();
            $data->firstGeneralEmail = $client->getFirstGeneralEmail();
            $data->firstGeneralPhone = $client->getFirstGeneralPhone();
        }

        return [
            'client' => $data,
        ];
    }

    private function getParametersClientAccountStatement(Client $client): array
    {
        $data = new TemplateData\Client();

        $organization = $client->getOrganization();
        $data->name = $client->getNameForView();
        $data->companyRegistrationNumber = $organization->getAccountStatementTemplateIncludeTaxInformation()
            ? $client->getCompanyRegistrationNumber()
            : '';
        $data->companyTaxId = $organization->getAccountStatementTemplateIncludeTaxInformation()
            ? $client->getCompanyTaxId()
            : '';

        $data->invoiceAddressSameAsContact = $client->getInvoiceAddressSameAsContact();
        $data->street1 = $client->getStreet1();
        $data->street2 = $client->getStreet2();
        $data->city = $client->getCity();
        $data->state = $client->getState() ? $client->getState()->getCode() : '';
        $data->zipCode = $client->getZipCode();
        $data->country = $client->getCountry() ? $client->getCountry()->getName() : '';

        $data->invoiceStreet1 = $client->getInvoiceStreet1();
        $data->invoiceStreet2 = $client->getInvoiceStreet2();
        $data->invoiceCity = $client->getInvoiceCity();
        $data->invoiceState = $client->getInvoiceState() ?
            $client->getInvoiceState()->getCode()
            : '';
        $data->invoiceZipCode = $client->getInvoiceZipCode();
        $data->invoiceCountry = $client->getInvoiceCountry() ?
            $client->getInvoiceCountry()->getName()
            : '';
        $data->attributes = $client->getAttributes()->toArray();

        $data->id = $client->getId();
        $data->userIdent = $client->getUserIdent();
        $data->type = $client->getClientType();
        $data->firstName = $client->getUser()->getFirstName();
        $data->lastName = $client->getUser()->getLastName();
        $data->username = $client->getUser()->getUsername();
        $data->companyName = $client->getCompanyName();
        $data->companyWebsite = $client->getCompanyWebsite();
        $data->companyContactFirstName = $client->getCompanyContactFirstName();
        $data->companyContactLastName = $client->getCompanyContactLastName();
        $data->suspendServicesIfPaymentIsOverdue = (bool) (
            $client->getStopServiceDue()
            ?? $this->options->get(Option::STOP_SERVICE_DUE)
        );
        $data->suspensionDelay = $client->getStopServiceDueDays()
            ?? $this->options->get(Option::STOP_SERVICE_DUE_DAYS);
        $data->previousIsp = $client->getPreviousIsp();
        $data->registrationDate = $client->getRegistrationDate()
            ? $this->formatter->formatDate(
                $client->getRegistrationDate(),
                Formatter::DEFAULT,
                Formatter::NONE
            )
            : null;
        $data->note = $client->getNote();
        $data->hasSuspendedService = $client->hasSuspendedService();
        $data->hasOutage = $client->hasOutage();
        $data->hasOverdueInvoice = $client->hasOverdueInvoice();

        // force re-calculation to always include current invoice in these variables
        $this->clientAccountStandingsCalculator->calculate($client);
        $currency = $client->getCurrencyCode();
        $data->accountBalanceRaw = $this->clientBalanceFormatter->getFormattedBalanceRaw($client->getBalance());
        $data->accountBalance = $this->clientBalanceFormatter->getFormattedBalance($client, $currency);
        $data->accountCredit = $this->formatter->formatCurrency(
            $client->getAccountStandingsCredit(),
            $currency,
            $client->getOrganization()->getLocale()
        );
        $data->accountOutstanding = $this->formatter->formatCurrency(
            $client->getAccountStandingsOutstanding(),
            $currency,
            $client->getOrganization()->getLocale()
        );

        foreach ($client->getContacts() as $clientContact) {
            $data->contacts[] = $this->getClientContact($clientContact);
        }

        $attributes = [];
        foreach ($client->getAttributes() as $attribute) {
            $attributes[$attribute->getAttribute()->getKey()] = $attribute->getValue() ?? '';
        }
        $data->attributes = $attributes;

        $data->firstBillingEmail = $client->getFirstBillingEmail();
        $data->firstBillingPhone = $client->getFirstBillingPhone();
        $data->firstGeneralEmail = $client->getFirstGeneralEmail();
        $data->firstGeneralPhone = $client->getFirstGeneralPhone();

        return [
            'client' => $data,
        ];
    }

    private function getClientContact(\AppBundle\Entity\ClientContact $clientContact): ClientContact
    {
        $data = new TemplateData\ClientContact();
        $data->name = $clientContact->getName();
        $data->phone = $clientContact->getPhone();
        $data->email = $clientContact->getEmail();
        foreach ($clientContact->getTypes() as $type) {
            $data->types[] = $this->getContactType($type);
        }

        return $data;
    }

    private function getContactType(\AppBundle\Entity\ContactType $contactType): ContactType
    {
        $data = new TemplateData\ContactType();
        $data->name = $contactType->getName();

        return $data;
    }

    public function getParametersOrganization(FinancialInterface $financial): array
    {
        $data = new TemplateData\Organization();
        $data->id = $financial->getOrganization() ? $financial->getOrganization()->getId() : null;
        $data->name = $financial->getOrganizationName();
        $data->registrationNumber = $financial->getTemplateIncludeTaxInformation()
            ? $financial->getOrganizationRegistrationNumber()
            : '';
        $data->taxId = $financial->getTemplateIncludeTaxInformation()
            ? $financial->getOrganizationTaxId()
            : '';
        $data->logo = '';
        $data->stamp = '';
        $data->street1 = $financial->getOrganizationStreet1();
        $data->street2 = $financial->getOrganizationStreet2();
        $data->city = $financial->getOrganizationCity();
        $data->country = $financial->getOrganizationCountry() ? $financial->getOrganizationCountry()->getName() : '';
        $data->state = $financial->getOrganizationState() ? $financial->getOrganizationState()->getCode() : '';
        $data->zipCode = $financial->getOrganizationZipCode();
        $data->bankAccount = $financial->getTemplateIncludeBankAccount()
            ? $financial->getOrganizationBankAccountFieldsForView()
            : '';
        $data->email = $financial->getOrganizationEmail();
        $data->phone = $financial->getOrganizationPhone();
        $data->website = $financial->getOrganizationWebsite();

        if ($logoPath = $financial->getOrganizationLogoPath()) {
            $logoPath = $this->filesystem->isAbsolutePath($logoPath)
                ? $logoPath
                : $this->packages->getUrl($logoPath, 'logo');
            $data->logo = $this->imagineDataFileProvider->getDataUri($logoPath, 'thumb_200x200');
            $data->logoOriginal = $this->imagineDataFileProvider->getDataUri($logoPath, null);
        }

        if ($stampPath = $financial->getOrganizationStampPath()) {
            $stampPath = $this->filesystem->isAbsolutePath($stampPath)
                ? $stampPath
                : $this->packages->getUrl($stampPath, 'stamp');
            $data->stamp = $this->imagineDataFileProvider->getDataUri($stampPath, 'thumb_200x200');
            $data->stampOriginal = $this->imagineDataFileProvider->getDataUri($stampPath, null);
        }

        $data->hasPaymentGateway = $financial->getOrganization()->hasPaymentGateway(
            (bool) $this->options->getGeneral(General::SANDBOX_MODE)
        );

        return [
            'organization' => $data,
        ];
    }

    private function getParametersOrganizationAccountStatement(Organization $organization): array
    {
        $data = new TemplateData\Organization();
        $data->id = $organization->getId();
        $data->name = $organization->getName();
        $data->registrationNumber = $organization->getAccountStatementTemplateIncludeTaxInformation()
            ? $organization->getRegistrationNumber()
            : '';
        $data->taxId = $organization->getAccountStatementTemplateIncludeTaxInformation()
            ? $organization->getTaxId()
            : '';
        $data->logo = '';
        $data->stamp = '';
        $data->street1 = $organization->getStreet1();
        $data->street2 = $organization->getStreet2();
        $data->city = $organization->getCity();
        $data->country = $organization->getCountry() ? $organization->getCountry()->getName() : '';
        $data->state = $organization->getState() ? $organization->getState()->getCode() : '';
        $data->zipCode = $organization->getZipCode();

        $data->bankAccount = $organization->getAccountStatementTemplateIncludeBankAccount()
            ? ($organization->getBankAccount()
                ? $organization->getBankAccount()->getFieldsForView()
                : ''
            )
            : '';

        $data->email = $organization->getEmail();
        $data->phone = $organization->getPhone();
        $data->website = $organization->getWebsite();

        if ($logoPath = $organization->getLogo()) {
            $logoPath = $this->filesystem->isAbsolutePath($logoPath)
                ? $logoPath
                : $this->packages->getUrl($logoPath, 'logo');
            $data->logo = $this->imagineDataFileProvider->getDataUri($logoPath, 'thumb_200x200');
            $data->logoOriginal = $this->imagineDataFileProvider->getDataUri($logoPath, null);
        }

        if ($stampPath = $organization->getStamp()) {
            $stampPath = $this->filesystem->isAbsolutePath($stampPath)
                ? $stampPath
                : $this->packages->getUrl($stampPath, 'stamp');
            $data->stamp = $this->imagineDataFileProvider->getDataUri($stampPath, 'thumb_200x200');
            $data->stampOriginal = $this->imagineDataFileProvider->getDataUri($stampPath, null);
        }

        $data->hasPaymentGateway = $organization->hasPaymentGateway(
            (bool) $this->options->getGeneral(General::SANDBOX_MODE)
        );

        return [
            'organization' => $data,
        ];
    }

    private function getParametersItems(FinancialInterface $financial): array
    {
        $items = $financial->getItemsSorted();
        $currency = $financial->getCurrency()->getCode();
        $parameterItems = $surcharges = [];
        foreach ($items as $item) {
            if ($item instanceof FinancialItemSurchargeInterface) {
                $itemData = $this->getItemData($item, $financial, $currency);
                $surcharges[$item->getService()->getId()][] = $itemData;
            }
        }
        foreach ($items as $item) {
            if ($item instanceof FinancialItemSurchargeInterface) {
                continue;
            }
            $itemData = $this->getItemData($item, $financial, $currency);
            if ($item instanceof FinancialItemServiceInterface) {
                if (0.0 !== round($item->getDiscountTotal(), 2)) {
                    $itemData->children[] = $this->getDiscountItemData(
                        $item,
                        $financial,
                        $currency
                    );
                }

                $serviceId = $item->getOriginalService()
                    ? $item->getOriginalService()->getId()
                    : ($item->getService() ? $item->getService()->getId() : null);

                if (array_key_exists($serviceId, $surcharges)) {
                    $itemData->children = array_merge(
                        $itemData->children,
                        $surcharges[$serviceId]
                    );
                }
            }
            $parameterItems[] = $itemData;
        }

        return [
            'items' => $parameterItems,
        ];
    }

    private function getItemData(
        FinancialItemInterface $item,
        FinancialInterface $financial,
        string $currency
    ): FinancialItem {
        $itemData = new FinancialItem();
        $itemData->label = $item->getLabel();

        // Append service period to label
        if ($item instanceof FinancialItemServiceInterface) {
            $nbsp = html_entity_decode('&nbsp;');

            $itemData->label = sprintf(
                '%s %s %s %s',
                $itemData->label,
                Strings::replace(
                    $this->formatter->formatDate($item->getInvoicedFrom(), Formatter::DEFAULT, Formatter::NONE),
                    '/ /',
                    $nbsp
                ),
                html_entity_decode('&ndash;'),
                Strings::replace(
                    $this->formatter->formatDate($item->getInvoicedTo(), Formatter::DEFAULT, Formatter::NONE),
                    '/ /',
                    $nbsp
                )
            );
        }

        $unit = ($item instanceof FinancialItemProductInterface || $item instanceof FinancialItemOtherInterface) && $item->getUnit(
        )
            ? ' ' . $item->getUnit()
            : '';
        $itemData->quantity = $this->formatter->formatNumber(
                round(
                    $item->getQuantity(),
                    $item instanceof FinancialItemServiceInterface || $item instanceof FinancialItemSurchargeInterface
                        ? 2
                        : 6
                ),
                'default',
                $this->getLocale($financial)
            ) . $unit;
        $itemData->quantityRaw = $item->getQuantity();

        $itemData->unit = $unit;

        $itemData->price = $this->formatter->formatNumber($item->getPrice(), 'default', $this->getLocale($financial));
        $itemData->priceRaw = $item->getPrice();
        $itemData->total = $financial->getItemRounding() === FinancialInterface::ITEM_ROUNDING_STANDARD
            ? $this->formatter->formatCurrency($item->getTotal(), $currency, $this->getLocale($financial))
            : $this->formatter->formatNumber($item->getTotal(), 'default', $this->getLocale($financial));
        $itemData->totalRaw = $item->getTotal();

        if ($financial->getPricingMode() === Option::PRICING_MODE_WITH_TAXES) {
            $itemData->taxAmountRaw = $item->getTaxRate1()
                ? $this->taxCalculator->calculateTax(
                    $item->getTotal(),
                    $item->getTaxRate1(),
                    $financial->getPricingMode(),
                    $financial->getTaxCoefficientPrecision()
                )
                : 0.0;
            $itemData->totalUntaxedRaw = $item->getTotal() - $itemData->taxAmountRaw;

            $itemData->priceUntaxedRaw =
                round($item->getQuantity(), 6) === 0.0
                    ? 0
                    : round($itemData->totalUntaxedRaw / $item->getQuantity(), 6);
            $itemData->priceUntaxed = $this->formatter->formatCurrency(
                $itemData->priceUntaxedRaw,
                $currency,
                $this->getLocale($financial)
            );
            $itemData->totalUntaxed = $this->formatter->formatCurrency(
                $itemData->totalUntaxedRaw,
                $currency,
                $this->getLocale($financial)
            );
            $itemData->taxRateRaw = $item->getTaxRate1();
            $itemData->taxRate = $itemData->taxRateRaw
                ? sprintf(
                    '%s%%',
                    $this->formatter->formatNumber($itemData->taxRateRaw, 'default', $this->getLocale($financial))
                )
                : html_entity_decode('&ndash;');
            $itemData->taxAmount = $this->formatter->formatCurrency(
                $itemData->taxAmountRaw,
                $currency,
                $this->getLocale($financial)
            );
        }

        switch (true) {
            case $item instanceof FinancialItemServiceInterface:
                $itemData->type = 'service';
                break;
            case $item instanceof FinancialItemSurchargeInterface:
                $itemData->type = 'surcharge';
                break;
            case $item instanceof FinancialItemProductInterface:
                $itemData->type = 'product';
                break;
            case $item instanceof FinancialItemFeeInterface:
                $itemData->type = 'fee';
                break;
            case $item instanceof FinancialItemOtherInterface:
                $itemData->type = 'custom';
                break;
        }

        return $itemData;
    }

    private function getDiscountItemData(
        FinancialItemServiceInterface $item,
        FinancialInterface $financial,
        string $currency
    ): FinancialItem {
        $itemData = new FinancialItem();
        $itemData->label = $item->getDiscountInvoiceLabel();
        $itemData->priceRaw = $item->getDiscountPrice();
        $itemData->price = $this->formatter->formatNumber(
            $itemData->priceRaw,
            'default',
            $this->getLocale($financial)
        );
        $itemData->quantity = $this->formatter->formatNumber(
            round($item->getDiscountQuantity(), 2),
            'default',
            $this->getLocale($financial)
        );
        $itemData->totalRaw = $item->getDiscountTotal();
        $itemData->total = $financial->getItemRounding() === FinancialInterface::ITEM_ROUNDING_STANDARD
            ? $this->formatter->formatCurrency($itemData->totalRaw, $currency, $this->getLocale($financial))
            : $this->formatter->formatNumber($itemData->totalRaw, 'default', $this->getLocale($financial));
        $itemData->type = 'discount';

        if ($financial->getPricingMode() === Option::PRICING_MODE_WITH_TAXES) {
            $itemData->taxAmountRaw = $item->getTaxRate1()
                ? $this->taxCalculator->calculateTax(
                    $item->getDiscountTotal(),
                    $item->getTaxRate1(),
                    $financial->getPricingMode(),
                    $financial->getTaxCoefficientPrecision()
                )
                : 0.0;
            $itemData->totalUntaxedRaw = $item->getDiscountTotal() - $itemData->taxAmountRaw;

            $itemData->priceUntaxedRaw = round($itemData->totalUntaxedRaw / $item->getDiscountQuantity(), 6);
            $itemData->priceUntaxed = $this->formatter->formatNumber(
                $itemData->priceUntaxedRaw,
                'default',
                $this->getLocale($financial)
            );
            $itemData->totalUntaxed = $this->formatter->formatCurrency(
                $itemData->totalUntaxedRaw,
                $currency,
                $this->getLocale($financial)
            );
            $itemData->taxRateRaw = $item->getTaxRate1();
            $itemData->taxRate = $itemData->taxRateRaw
                ? sprintf(
                    '%s%%',
                    $this->formatter->formatNumber($itemData->taxRateRaw, 'default', $this->getLocale($financial))
                )
                : html_entity_decode('&ndash;');
            $itemData->taxAmount = $this->formatter->formatCurrency(
                $itemData->taxAmountRaw,
                $currency,
                $this->getLocale($financial)
            );
        }

        return $itemData;
    }

    private function getParametersInvoiceTotals(Invoice $invoice, bool $includePotentialCredit = false): array
    {
        $data = new InvoiceTotals();
        $this->setTotalsData($data, $invoice);

        $currency = $invoice->getCurrency()->getCode();
        $data->hasPayment = 0.0 !== round($invoice->getAmountPaid(), 2);
        $data->amountPaidRaw = $invoice->getAmountPaid();
        $data->amountPaid = $this->formatter->formatCurrency($data->amountPaidRaw, $currency);
        $data->amountDueRaw = $invoice->getAmountToPay();
        $data->amountDue = $data->balanceDue = $this->formatter->formatCurrency($data->amountDueRaw, $currency);
        $data->hasCustomRounding = $invoice->hasCustomTotalRounding();
        $data->totalRoundingDifferenceRaw = $invoice->getTotalRoundingDifference();
        $data->totalRoundingDifference = $this->formatter->formatCurrency($data->totalRoundingDifferenceRaw, $currency);
        $data->totalBeforeRoundingRaw = $data->totalRaw - $data->totalRoundingDifferenceRaw;
        $data->totalBeforeRounding = $this->formatter->formatCurrency($data->totalBeforeRoundingRaw, $currency);

        if ($includePotentialCredit) {
            $this->setCreditData($data, $invoice);
        }

        return [
            'totals' => $data,
        ];
    }

    private function getParametersQuoteTotals(Quote $quote): array
    {
        $data = new QuoteTotals();
        $this->setTotalsData($data, $quote);

        return [
            'totals' => $data,
        ];
    }

    private function setTotalsData(Totals $data, FinancialInterface $financial): void
    {
        $currency = $financial->getCurrency()->getCode();

        $data->subtotalRaw = $financial->getSubtotal();
        $data->subtotal = $this->formatter->formatCurrency(
            $data->subtotalRaw,
            $currency,
            $this->getLocale($financial)
        );
        $data->totalRaw = $financial->getTotal();
        $data->total = $this->formatter->formatCurrency(
            $data->totalRaw,
            $currency,
            $this->getLocale($financial)
        );
        $data->discountPriceRaw = $financial->getTotalDiscount();
        $data->hasDiscount = round($data->discountPriceRaw, 2) !== 0.0;
        $data->discountPrice = $this->formatter->formatCurrency(
            $data->discountPriceRaw,
            $currency,
            $this->getLocale($financial)
        );
        $data->discountLabel = sprintf(
            '%s - %s %%',
            $financial->getDiscountInvoiceLabel(),
            $financial->getDiscountValue()
        );

        $taxes = $financial->getTotalTaxes();
        foreach ($taxes as $taxLabel => $taxPrice) {
            $taxTotal = new TaxTotal();
            $taxTotal->label = $taxLabel;
            $taxTotal->price = $this->formatter->formatCurrency($taxPrice, $currency, $this->getLocale($financial));
            $taxTotal->priceRaw = $taxPrice;
            $data->taxes[] = $taxTotal;
        }

        $data->hasCustomRounding = $financial->hasCustomTotalRounding();
        $data->totalRoundingDifferenceRaw = $financial->getTotalRoundingDifference();
        $data->totalRoundingDifference = $this->formatter->formatCurrency(
            $data->totalRoundingDifferenceRaw,
            $currency,
            $this->getLocale($financial)
        );

        $data->totalBeforeRoundingRaw = $financial->getTotal() - $financial->getTotalRoundingDifference();
        $data->totalBeforeRounding = $this->formatter->formatCurrency(
            $data->totalBeforeRoundingRaw,
            $currency,
            $this->getLocale($financial)
        );

        $data->hasTotalRounding = (bool) $financial->getTotalRoundingDifference();
    }

    private function setCreditData(InvoiceTotals $data, Invoice $invoice): void
    {
        $currency = $invoice->getCurrency()->getCode();

        $credit = $this->invoiceCalculations->calculatePotentialCredit($invoice);
        $amountPaid = $credit->amountPaid;
        $amountToPay = $credit->amountToPay;
        $amountPotentiallyPaidFromCredit = $credit->amountFromCredit;

        $data->hasPayment = round($amountPaid, 2) !== 0.0;
        $data->hasCreditPayment = round($amountPotentiallyPaidFromCredit, 2) !== 0.0;

        $data->amountPaidRaw = $amountPaid;
        $data->amountPaid = $this->formatter->formatCurrency(
            $data->amountPaidRaw,
            $currency,
            $this->getLocale($invoice)
        );
        $data->amountDueRaw = $amountToPay;
        $data->amountDue = $data->balanceDue = $this->formatter->formatCurrency(
            $data->amountDueRaw,
            $currency,
            $this->getLocale($invoice)
        );
    }

    private function getLocale(FinancialInterface $financial): ?string
    {
        $locale = $financial->getOrganization()
            ? $financial->getOrganization()->getLocale()
            : null;

        return ! $locale && $financial->getClient()
            ? $financial->getClient()->getOrganization()->getLocale()
            : $locale;
    }

    private function getTaxRecapitulation(FinancialInterface $financial): array
    {
        if ($financial->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES) {
            return [];
        }

        $items = $financial->getItems();
        $currency = $financial->getCurrency()->getCode();

        $totalDiscountCoefficient = (100 - $financial->getDiscountValue() ?? 0) / 100;

        $taxRecapitulations = [];

        $taxRecapitulationTotal = new TaxRecapitulation();
        $taxRecapitulationTotal->priceUntaxedRaw = 0.0;
        $taxRecapitulationTotal->priceWithTaxRaw = 0.0;
        $taxRecapitulationTotal->taxAmountRaw = 0.0;

        foreach ($items as $item) {
            $total = $item instanceof FinancialItemServiceInterface
                ? $item->getTotal() + ($item->getDiscountTotal() ?? 0)
                : $item->getTotal();
            $total *= $totalDiscountCoefficient;

            $taxAmount = $item->getTaxRate1()
                ? $this->taxCalculator->calculateTax(
                    $total,
                    $item->getTaxRate1(),
                    $financial->getPricingMode(),
                    $financial->getTaxCoefficientPrecision()
                )
                : 0;

            $taxRate1key = $item->getTaxRate1() ? (string) $item->getTaxRate1() : '0';
            /** @var TaxRecapitulation $taxRecapitulation */
            if ($taxRecapitulation = $taxRecapitulations[$taxRate1key] ?? false) {
                $taxRecapitulation->priceUntaxedRaw += $total - $taxAmount;
                $taxRecapitulation->priceWithTaxRaw += $total;
                $taxRecapitulation->taxAmountRaw += $taxAmount;
            } else {
                $taxRecapitulation = new TaxRecapitulation();
                $taxRecapitulation->taxName = $item->getTax1() ? $item->getTax1()->getName() : '';
                $taxRecapitulation->taxRate = $item->getTaxRate1();
                $taxRecapitulation->priceUntaxedRaw = $total - $taxAmount;
                $taxRecapitulation->priceWithTaxRaw = $total;
                $taxRecapitulation->taxAmountRaw = $taxAmount;

                $taxRecapitulations[$taxRate1key] = $taxRecapitulation;
            }
        }

        foreach ($taxRecapitulations as $taxRecapitulation) {
            $taxRecapitulationTotal->priceUntaxedRaw += $taxRecapitulation->priceUntaxedRaw;
            $taxRecapitulationTotal->priceWithTaxRaw += $taxRecapitulation->priceWithTaxRaw;
            $taxRecapitulationTotal->taxAmountRaw += $taxRecapitulation->taxAmountRaw;
        }

        ksort($taxRecapitulations);
        $taxRecapitulations[] = $taxRecapitulationTotal;

        return [
            'taxRecapitulation' => array_map(
                function (TaxRecapitulation $taxRecapitulation) use ($currency, $financial) {
                    $taxRecapitulation->priceUntaxed = $this->formatter->formatCurrency(
                        $taxRecapitulation->priceUntaxedRaw,
                        $currency,
                        $this->getLocale($financial)
                    );
                    $taxRecapitulation->priceWithTax = $this->formatter->formatCurrency(
                        $taxRecapitulation->priceWithTaxRaw,
                        $currency,
                        $this->getLocale($financial)
                    );
                    $taxRecapitulation->taxAmount = $this->formatter->formatCurrency(
                        $taxRecapitulation->taxAmountRaw,
                        $currency,
                        $this->getLocale($financial)
                    );

                    return $taxRecapitulation;
                },
                $taxRecapitulations
            ),
        ];
    }
}
