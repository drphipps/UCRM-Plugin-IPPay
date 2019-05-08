<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientAttribute;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Option;
use AppBundle\Service\Financial\NextFinancialNumberFactory;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FinancialFactory
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var NextFinancialNumberFactory
     */
    private $nextFinancialNumberFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        Options $options,
        NextFinancialNumberFactory $nextFinancialNumberFactory,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager
    ) {
        $this->options = $options;
        $this->nextFinancialNumberFactory = $nextFinancialNumberFactory;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    public function createInvoice(Client $client, \DateTimeImmutable $date): Invoice
    {
        $invoice = $this->prepareInvoice($client, $date);

        $invoice->setInvoiceNumber($this->nextFinancialNumberFactory->createInvoiceNumber($client->getOrganization()));

        return $invoice;
    }

    public function createProformaInvoice(Client $client, \DateTimeImmutable $date): Invoice
    {
        $invoice = $this->prepareInvoice($client, $date);

        $invoice->setIsProforma(true);
        $invoice->setInvoiceNumber(
            $this->nextFinancialNumberFactory->createProformaInvoiceNumber($client->getOrganization())
        );

        return $invoice;
    }

    public function createQuote(Client $client, \DateTimeImmutable $date): Quote
    {
        $quote = new Quote();
        $quote->setQuoteNumber($this->nextFinancialNumberFactory->createQuoteNumber($client->getOrganization()));
        $organization = $client->getOrganization();
        $quote->setTemplateIncludeBankAccount($organization->getQuoteTemplateIncludeBankAccount());
        $quote->setTemplateIncludeTaxInformation($organization->getQuoteTemplateIncludeTaxInformation());
        $quote->setQuoteTemplate($organization->getQuoteTemplate());
        $quote->setNotes($organization->getQuoteTemplateDefaultNotes());

        $this->setData($quote, $client, $date);

        return $quote;
    }

    private function setData(FinancialInterface $item, Client $client, \DateTimeImmutable $date): void
    {
        $this->entityManager->getRepository(ClientAttribute::class)->loadAttributes($client);
        $organization = $client->getOrganization();

        $item->setCreatedDate(DateTimeFactory::createFromInterface($date));
        $item->setClient($client);
        $item->setItemRounding($this->options->get(Option::INVOICE_ITEM_ROUNDING));
        $item->setTaxRounding($this->options->get(Option::INVOICE_TAX_ROUNDING));
        $item->setPricingMode($this->options->get(Option::PRICING_MODE));
        $item->setTaxCoefficientPrecision($this->options->get(Option::PRICING_TAX_COEFFICIENT_PRECISION));
        $item->setDiscountInvoiceLabel(
            $this->options->get(Option::DISCOUNT_INVOICE_LABEL) ?: $this->translator->trans('Discount')
        );
        $item->setCurrency($organization->getCurrency());
        $item->setClientFirstName($client->getUser()->getFirstName());
        $item->setClientLastName($client->getUser()->getLastName());
        $item->setClientCompanyName($client->getCompanyName());
        $item->setClientStreet1($client->getStreet1());
        $item->setClientStreet2($client->getStreet2());
        $item->setClientCity($client->getCity());
        $item->setClientZipCode($client->getZipCode());
        $item->setClientCompanyRegistrationNumber($client->getCompanyRegistrationNumber());
        $item->setClientCompanyTaxId($client->getCompanyTaxId());
        $item->setClientPhone($client->getFirstPhone());
        $item->setClientEmail($client->getFirstBillingEmail());
        $item->setClientInvoiceStreet1($client->getInvoiceStreet1());
        $item->setClientInvoiceStreet2($client->getInvoiceStreet2());
        $item->setClientInvoiceCity($client->getInvoiceCity());
        $item->setClientInvoiceCountry($client->getInvoiceCountry());
        $item->setClientInvoiceZipCode($client->getInvoiceZipCode());
        $item->setClientInvoiceAddressSameAsContact($client->getInvoiceAddressSameAsContact());
        $item->setClientState($client->getState());
        $item->setClientCountry($client->getCountry());
        $item->setClientInvoiceState($client->getInvoiceState());

        $attributes = [];
        foreach ($client->getAttributes() as $attribute) {
            $attributes[$attribute->getAttribute()->getKey()] = $attribute->getValue() ?? '';
        }
        $item->setClientAttributes($attributes);

        $item->setOrganization($organization);
        $item->setOrganizationName($organization->getName());
        $item->setOrganizationRegistrationNumber($organization->getRegistrationNumber());
        $item->setOrganizationTaxId($organization->getTaxId());
        $item->setOrganizationEmail($organization->getEmail());
        $item->setOrganizationPhone($organization->getPhone());
        $item->setOrganizationWebsite($organization->getWebsite());
        $item->setOrganizationStreet1($organization->getStreet1());
        $item->setOrganizationStreet2($organization->getStreet2());
        $item->setOrganizationCity($organization->getCity());
        $item->setOrganizationZipCode($organization->getZipCode());
        $item->setOrganizationCountry($organization->getCountry());
        $item->setOrganizationState($organization->getState());
        $item->setOrganizationLogoPath($organization->getLogo());
        $item->setOrganizationStampPath($organization->getStamp());

        $bankAccount = $organization->getBankAccount();
        if ($bankAccount) {
            $item->setOrganizationBankAccountField1($bankAccount->getField1());
            $item->setOrganizationBankAccountField2($bankAccount->getField2());
            $item->setOrganizationBankAccountName($bankAccount->getName());
        }
    }

    private function prepareInvoice(Client $client, \DateTimeImmutable $date): Invoice
    {
        $invoice = new Invoice();
        $invoice->setInvoiceStatus(Invoice::UNPAID);
        $organization = $client->getOrganization();
        $invoiceMaturityDays = $client->getInvoiceMaturityDays() ?? $organization->getInvoiceMaturityDays();
        $dueDateModify = sprintf('+%d days', $invoiceMaturityDays);
        $invoice->setDueDate(DateTimeFactory::createFromInterface($date)->modify($dueDateModify));
        $invoice->setInvoiceMaturityDays($invoiceMaturityDays);
        $invoice->setTemplateIncludeBankAccount($organization->getInvoiceTemplateIncludeBankAccount());
        $invoice->setTemplateIncludeTaxInformation($organization->getInvoiceTemplateIncludeTaxInformation());
        $invoice->setInvoiceTemplate($organization->getInvoiceTemplate());
        $invoice->setProformaInvoiceTemplate($organization->getProformaInvoiceTemplate());
        $invoice->setNotes($organization->getInvoiceTemplateDefaultNotes());
        $invoice->setTotalRoundingPrecision(
            $organization->getInvoicedTotalRoundingPrecision() ?? $organization->getCurrency()->getFractionDigits()
        );
        $invoice->setTotalRoundingMode($organization->getInvoicedTotalRoundingMode());

        $this->setData($invoice, $client, $date);

        return $invoice;
    }
}
