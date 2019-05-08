<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Payment;

use AppBundle\Component\Imagine\ImagineDataFileProvider;
use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentCustom;
use AppBundle\Entity\PaymentDetailsInterface;
use AppBundle\Entity\PaymentPayPal;
use AppBundle\Entity\PaymentStripe;
use AppBundle\Entity\Refund;
use AppBundle\Service\Client\ClientBalanceFormatter;
use AppBundle\Service\Financial\TemplateData;
use AppBundle\Service\Options;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentReceiptTemplateParametersProvider
{
    public const PARAM_ORGANIZATION_LOGO = '{{ organization.logo }}';
    public const PARAM_ORGANIZATION_LOGO_ORIGINAL = '{{ organization.logoOriginal }}';
    public const PARAM_ORGANIZATION_STAMP = '{{ organization.stamp }}';
    public const PARAM_ORGANIZATION_STAMP_ORIGINAL = '{{ organization.stampOriginal }}';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
     * @var Options
     */
    private $options;

    /**
     * @var ClientBalanceFormatter
     */
    private $clientBalanceFormatter;

    public function __construct(
        EntityManagerInterface $entityManager,
        Formatter $formatter,
        TranslatorInterface $translator,
        ImagineDataFileProvider $imagineDataFileProvider,
        Packages $packages,
        Filesystem $filesystem,
        Options $options,
        ClientBalanceFormatter $clientBalanceFormatter
    ) {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
        $this->translator = $translator;
        $this->imagineDataFileProvider = $imagineDataFileProvider;
        $this->packages = $packages;
        $this->filesystem = $filesystem;
        $this->options = $options;
        $this->clientBalanceFormatter = $clientBalanceFormatter;
    }

    public function getParameters(Payment $payment, bool $forEmail = false): array
    {
        return array_merge(
            $this->getParametersPayment($payment),
            $this->getParametersClient($payment),
            $this->getParametersOrganization($payment, $forEmail),
            $this->getParametersPaymentDetail($payment)
        );
    }

    public function getParametersOnlyPayment(Payment $payment): TemplateData\Payment
    {
        return $this->getParametersPayment($payment)['payment'];
    }

    private function getParametersPayment(Payment $payment): array
    {
        $data = new TemplateData\Payment();
        $data->id = $payment->getId();
        $data->receiptNumber = $payment->getReceiptNumber();
        $data->createdDate = $this->formatter->formatDate(
            $payment->getCreatedDate(),
            Formatter::DEFAULT,
            Formatter::SHORT
        );
        $data->createdDateISO8601 = $payment->getCreatedDate()
            ? $payment->getCreatedDate()->format(\DateTime::ATOM)
            : (new \DateTime())->format(\DateTime::ATOM);
        $data->currency = $payment->getCurrency()->getCode();
        $data->amountRaw = $payment->getAmount();
        $data->amount = $this->formatter->formatCurrency(
            $data->amountRaw,
            $data->currency,
            $payment->getClient() ? $payment->getClient()->getOrganization()->getLocale() : null
        );
        $data->note = $payment->getNote();
        $data->method = $this->translator->trans($payment->getMethodName());
        $data->checkNumber = $payment->getCheckNumber();
        if ($payment->getCredit()) {
            $data->creditRaw = $payment->getCredit()->getAmount();
            $data->credit = $this->formatter->formatCurrency(
                $data->creditRaw,
                $data->currency,
                $payment->getClient() ? $payment->getClient()->getOrganization()->getLocale() : null
            );
        }

        foreach ($payment->getPaymentCoversInvoices() as $cover) {
            $dataCover = new TemplateData\PaymentCover();

            $dataCover->amountRaw = $cover->getAmount();
            $dataCover->amount = $this->formatter->formatCurrency(
                $dataCover->amountRaw,
                $data->currency,
                $payment->getClient() ? $payment->getClient()->getOrganization()->getLocale() : null
            );
            $dataCover->invoiceNumber = $cover->getInvoice()->getInvoiceNumber();
            $dataCover->invoiceCreatedDate = $this->formatter->formatDate(
                $cover->getInvoice()->getCreatedDate(),
                Formatter::ALTERNATIVE,
                Formatter::NONE
            );
            $dataCover->invoiceDueDate = $this->formatter->formatDate(
                $cover->getInvoice()->getDueDate(),
                Formatter::ALTERNATIVE,
                Formatter::NONE
            );
            $dataCover->invoiceTotalRaw = $cover->getInvoice()->getTotal();
            $dataCover->invoiceTotal = $this->formatter->formatCurrency(
                $dataCover->invoiceTotalRaw,
                $data->currency
            );
            $dataCover->invoiceBalanceDueRaw = $cover->getInvoice()->getAmountToPay();
            $dataCover->invoiceBalanceDue = $this->formatter->formatCurrency(
                $dataCover->invoiceBalanceDueRaw,
                $data->currency
            );
            $attributes = [];
            foreach ($cover->getInvoice()->getAttributes() as $attribute) {
                $attributes[$attribute->getAttribute()->getKey()] = $attribute->getValue() ?? '';
            }
            $dataCover->invoiceAttributes = $attributes;

            $data->covers[] = $dataCover;
        }

        return [
            'payment' => $data,
        ];
    }

    public function getParametersRefund(Refund $refund): TemplateData\Refund
    {
        $data = new TemplateData\Refund();
        $data->id = $refund->getId();
        $data->createdDate = $this->formatter->formatDate(
            $refund->getCreatedDate(),
            Formatter::DEFAULT,
            Formatter::SHORT
        );
        $data->createdDateISO8601 = $refund->getCreatedDate()
            ? $refund->getCreatedDate()->format(\DateTime::ATOM)
            : (new \DateTime())->format(\DateTime::ATOM);
        $data->currency = $refund->getCurrency()->getCode();
        $data->amountRaw = $refund->getAmount();
        $data->amount = $this->formatter->formatCurrency(
            $data->amountRaw,
            $data->currency,
            $refund->getClient() ? $refund->getClient()->getOrganization()->getLocale() : null
        );
        $data->note = $refund->getNote();
        $data->method = $this->translator->trans($refund->getMethodName());

        return $data;
    }

    public function getParametersClient(Payment $payment): array
    {
        $data = new TemplateData\Client();
        $client = $payment->getClient();
        if (! $client) {
            throw new \InvalidArgumentException('Payment must have client for receipt to be rendered.');
        }

        $data->name = $client->getNameForView();
        $data->companyRegistrationNumber = $client->getCompanyRegistrationNumber() ?? '';
        $data->companyTaxId = $client->getCompanyTaxId() ?? '';

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

        $attributes = [];
        foreach ($client->getAttributes() as $attribute) {
            $attributes[$attribute->getAttribute()->getKey()] = $attribute->getValue() ?? '';
        }
        $data->attributes = $attributes;

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
        $currency = $payment->getCurrency()->getCode();
        $data->accountBalanceRaw = $this->clientBalanceFormatter->getFormattedBalanceRaw($client->getBalance());
        $data->accountBalance = $this->clientBalanceFormatter->getFormattedBalance($client, $currency);
        $data->accountCreditRaw = $client->getAccountStandingsCredit();
        $data->accountCredit = $this->formatter->formatCurrency(
            $data->accountCreditRaw,
            $currency,
            $client->getOrganization()->getLocale()
        );
        $data->accountOutstandingRaw = $client->getAccountStandingsOutstanding();
        $data->accountOutstanding = $this->formatter->formatCurrency(
            $data->accountOutstandingRaw,
            $currency,
            $client->getOrganization()->getLocale()
        );

        foreach ($client->getContacts() as $clientContact) {
            $data->contacts[] = $this->getClientContact($clientContact);
        }

        $data->firstBillingEmail = $client->getFirstBillingEmail();
        $data->firstBillingPhone = $client->getFirstBillingPhone();
        $data->firstGeneralEmail = $client->getFirstGeneralEmail();
        $data->firstGeneralPhone = $client->getFirstGeneralPhone();

        return [
            'client' => $data,
        ];
    }

    public function getParametersOrganization(Payment $payment, bool $forEmail = false): array
    {
        $client = $payment->getClient();
        if (! $client) {
            throw new \InvalidArgumentException('Payment must have client for receipt to be rendered.');
        }

        $data = new TemplateData\Organization();
        $organization = $client->getOrganization();
        $data->id = $organization->getId();
        $data->name = $organization->getName();
        $data->registrationNumber = $organization->getRegistrationNumber() ?? '';
        $data->taxId = $organization->getTaxId() ?? '';
        $data->logo = '';
        $data->stamp = '';
        $data->street1 = $organization->getStreet1();
        $data->street2 = $organization->getStreet2();
        $data->city = $organization->getCity();
        $data->country = $organization->getCountry() ? $organization->getCountry()->getName() : '';
        $data->state = $organization->getState() ? $organization->getState()->getCode() : '';
        $data->zipCode = $organization->getZipCode();
        $data->bankAccount = $organization->getBankAccount()
            ? $organization->getBankAccount()->getFieldsForView()
            : '';
        $data->email = $organization->getEmail();
        $data->phone = $organization->getPhone();
        $data->website = $organization->getWebsite();

        if ($logoPath = $organization->getLogo()) {
            $logoPath = $this->filesystem->isAbsolutePath($logoPath)
                ? $logoPath
                : $this->packages->getUrl($logoPath, 'logo');
            $data->logo = $forEmail
                ? self::PARAM_ORGANIZATION_LOGO
                : $this->imagineDataFileProvider->getDataUri($logoPath, 'thumb_200x200');
            $data->logoOriginal = $forEmail
                ? self::PARAM_ORGANIZATION_LOGO_ORIGINAL
                : $this->imagineDataFileProvider->getDataUri($logoPath, null);
        }

        if ($stampPath = $organization->getStamp()) {
            $stampPath = $this->filesystem->isAbsolutePath($stampPath)
                ? $stampPath
                : $this->packages->getUrl($stampPath, 'stamp');
            $data->stamp = $forEmail
                ? self::PARAM_ORGANIZATION_STAMP
                : $this->imagineDataFileProvider->getDataUri($stampPath, 'thumb_200x200');
            $data->stampOriginal = $forEmail
                ? self::PARAM_ORGANIZATION_STAMP_ORIGINAL
                : $this->imagineDataFileProvider->getDataUri($stampPath, null);
        }

        $data->hasPaymentGateway = $organization->hasPaymentGateway(
            (bool) $this->options->getGeneral(General::SANDBOX_MODE)
        );

        return [
            'organization' => $data,
        ];
    }

    public function getImagesOrganization(Payment $payment): array
    {
        $client = $payment->getClient();
        if (! $client) {
            throw new \InvalidArgumentException('Payment must have client for receipt to be rendered.');
        }
        $organization = $client->getOrganization();

        $images = [];
        if ($logoPath = $organization->getLogo()) {
            $logoPath = $this->filesystem->isAbsolutePath($logoPath)
                ? $logoPath
                : $this->packages->getUrl($logoPath, 'logo');

            $images[self::PARAM_ORGANIZATION_LOGO] = $this->imagineDataFileProvider->getImageFileContent(
                $logoPath,
                'thumb_200x200'
            );
            $images[self::PARAM_ORGANIZATION_LOGO_ORIGINAL] = $this->imagineDataFileProvider->getImageFileContent(
                $logoPath
            );
        }

        if ($stampPath = $organization->getStamp()) {
            $stampPath = $this->filesystem->isAbsolutePath($stampPath)
                ? $stampPath
                : $this->packages->getUrl($stampPath, 'stamp');
            $images[self::PARAM_ORGANIZATION_STAMP] = $this->imagineDataFileProvider->getImageFileContent(
                $stampPath,
                'thumb_200x200'
            );
            $images[self::PARAM_ORGANIZATION_STAMP_ORIGINAL] = $this->imagineDataFileProvider->getImageFileContent(
                $stampPath
            );
        }

        return $images;
    }

    private function getClientContact(\AppBundle\Entity\ClientContact $clientContact): TemplateData\ClientContact
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

    private function getContactType(\AppBundle\Entity\ContactType $contactType): TemplateData\ContactType
    {
        $data = new TemplateData\ContactType();
        $data->name = $contactType->getName();

        return $data;
    }

    private function getParametersPaymentDetail(Payment $payment): array
    {
        $data = new TemplateData\PaymentDetail();

        if ($this->options->get(Option::CLIENT_ZONE_PAYMENT_DETAILS)) {
            $paymentDetails = null;
            if ($payment->getPaymentDetails()) {
                $paymentDetails = $payment->getPaymentDetails();
            } elseif ($payment->getProvider() && $payment->getPaymentDetailsId()) {
                /** @var PaymentDetailsInterface $paymentDetails */
                $paymentDetails = $this->entityManager->find(
                    $payment->getProvider()->getPaymentDetailsClass(),
                    $payment->getPaymentDetailsId()
                );
            }

            if ($paymentDetails) {
                $data->providerName = $paymentDetails->getProviderName();
                $data->transactionId = $paymentDetails->getTransactionId();
                $data->detailsArray = $this->getPaymentDetailsArray($paymentDetails);
            }
        }

        return [
            'paymentDetail' => $data,
        ];
    }

    private function getPaymentDetailsArray(PaymentDetailsInterface $paymentDetails): array
    {
        $properties = [
            'Provider name' => $paymentDetails->getProviderName(),
            'Transaction ID' => $paymentDetails->getTransactionId(),
        ];

        if ($paymentDetails instanceof PaymentCustom) {
            $properties['Time'] = $this->formatter->formatDate(
                $paymentDetails->getProviderPaymentTime(),
                Formatter::DEFAULT,
                Formatter::SHORT
            );
        }
        if ($paymentDetails instanceof PaymentPayPal) {
            $properties['State'] = $paymentDetails->getState();
            $properties['Type'] = $paymentDetails->getType();
        }
        if ($paymentDetails instanceof PaymentStripe) {
            $properties['Request ID'] = $paymentDetails->getRequestId();
            $properties['Balance transaction'] = $paymentDetails->getBalanceTransaction();
            $properties['Customer'] = $paymentDetails->getBalanceTransaction();
            $properties['Source card ID'] = $paymentDetails->getSourceCardId();
            $properties['Source name'] = $paymentDetails->getSourceName();
            $properties['Status'] = $paymentDetails->getStatus();
        }

        $return = [];
        foreach ($properties as $name => $property) {
            if ($property) {
                $return[$this->translator->trans($name)] = $property;
            }
        }

        return $return;
    }
}
