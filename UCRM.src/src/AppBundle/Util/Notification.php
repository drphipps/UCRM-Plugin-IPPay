<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\Service;
use AppBundle\Service\Client\ClientBalanceFormatter;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Translation\TranslatorInterface;
use TicketingBundle\Entity\Ticket;

class Notification
{
    private const RAW_REPLACEMENTS = [
        '%CREATED_LIST%',
        '%TICKET_EMAIL_FOOTER%',
        '%PAYMENT_RECEIPT%',
        '%PAYMENT_RECEIPT_PDF%',
    ];

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var array
     */
    private $replacements = [];

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $bodyTemplate;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $extraCss = '';

    /**
     * @var ClientBalanceFormatter
     */
    private $clientBalanceFormatter;

    public function __construct(
        Formatter $formatter,
        TranslatorInterface $translator,
        ClientBalanceFormatter $clientBalanceFormatter
    ) {
        $this->formatter = $formatter;
        $this->translator = $translator;
        $this->clientBalanceFormatter = $clientBalanceFormatter;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function setBodyTemplate(string $bodyTemplate): void
    {
        $this->bodyTemplate = $bodyTemplate;
    }

    public function addReplacement(string $key, string $value): void
    {
        if (isset($this->replacements[$key])) {
            throw new \OutOfBoundsException('Key already exists.');
        }
        $this->replacements[$key] = $value;
    }

    public function setClient(Client $client): void
    {
        if ($client->isCompany()) {
            $this->replacements['%CLIENT_FIRST_NAME%'] = ucfirst($client->getCompanyContactFirstName() ?? '');
            $this->replacements['%CLIENT_LAST_NAME%'] = ucfirst($client->getCompanyContactLastName() ?? '');
        } else {
            $this->replacements['%CLIENT_FIRST_NAME%'] = ucfirst($client->getFirstName() ?? '');
            $this->replacements['%CLIENT_LAST_NAME%'] = ucfirst($client->getLastName() ?? '');
        }
        $this->replacements['%CLIENT_COMPANY_NAME%'] = $client->getCompanyName();
        $this->replacements['%CLIENT_NAME%'] = $client->getNameForView();
        $this->replacements['%CLIENT_ID%'] = $client->getId();
        $this->replacements['%CLIENT_USER_IDENT%'] = $client->getUserIdent();
        $this->replacements['%CLIENT_USERNAME%'] = $client->getUser()->getUsername();

        $currencyCode = $client->getCurrencyCode();
        $this->replacements['%CLIENT_ACCOUNT_BALANCE%'] = $this->clientBalanceFormatter->getFormattedBalance(
            $client,
            $currencyCode
        );
        $this->replacements['%CLIENT_ACCOUNT_CREDIT%'] = $this->formatter->formatCurrency(
            $client->getAccountStandingsCredit(),
            $currencyCode,
            $client->getOrganization()->getLocale()
        );
        $this->replacements['%CLIENT_ACCOUNT_OUTSTANDING%'] = $this->formatter->formatCurrency(
            $client->getAccountStandingsOutstanding(),
            $currencyCode,
            $client->getOrganization()->getLocale()
        );

        $this->replacements['%CLIENT_STREET1%'] = $client->getStreet1();
        $this->replacements['%CLIENT_STREET2%'] = $client->getStreet2();
        $this->replacements['%CLIENT_CITY%'] = $client->getCity();
        $this->replacements['%CLIENT_ZIP_CODE%'] = $client->getZipCode();
        $this->replacements['%CLIENT_COUNTRY%'] = $client->getCountry() ? $client->getCountry()->getName() : '';
        $this->replacements['%CLIENT_STATE%'] = $client->getState() ? $client->getState()->getName() : '';

        $this->replacements['%CLIENT_INVOICE_STREET1%'] = $client->getInvoiceAddressSameAsContact()
            ? $client->getStreet1()
            : $client->getInvoiceStreet1();
        $this->replacements['%CLIENT_INVOICE_STREET2%'] = $client->getInvoiceAddressSameAsContact()
            ? $client->getStreet2()
            : $client->getInvoiceStreet2();
        $this->replacements['%CLIENT_INVOICE_CITY%'] = $client->getInvoiceAddressSameAsContact()
            ? $client->getCity()
            : $client->getInvoiceCity();
        $this->replacements['%CLIENT_INVOICE_ZIP_CODE%'] = $client->getInvoiceAddressSameAsContact()
            ? $client->getZipCode()
            : $client->getInvoiceZipCode();
        $this->replacements['%CLIENT_INVOICE_COUNTRY%'] = $client->getInvoiceAddressSameAsContact()
            ? ($client->getCountry() ? $client->getCountry()->getName() : '')
            : ($client->getInvoiceCountry() ? $client->getInvoiceCountry()->getName() : '');
        $this->replacements['%CLIENT_INVOICE_STATE%'] = $client->getInvoiceAddressSameAsContact()
            ? ($client->getState() ? $client->getState()->getName() : '')
            : ($client->getInvoiceState() ? $client->getInvoiceState()->getName() : '');

        $organization = $client->getOrganization();
        $this->replacements['%ORGANIZATION_NAME%'] = $organization->getName() ?: '';
        $this->replacements['%ORGANIZATION_REGISTRATION_NUMBER%'] = $organization->getRegistrationNumber() ?: '';
        $this->replacements['%ORGANIZATION_TAX_ID%'] = $organization->getTaxId() ?: '';
        $this->replacements['%ORGANIZATION_STREET1%'] = $organization->getStreet1() ?: '';
        $this->replacements['%ORGANIZATION_STREET2%'] = $organization->getStreet2() ?: '';
        $this->replacements['%ORGANIZATION_CITY%'] = $organization->getCity() ?: '';
        $this->replacements['%ORGANIZATION_COUNTRY%'] = $organization->getCountry()->getName() ?: '';
        $this->replacements['%ORGANIZATION_STATE%'] = $organization->getState()
            ? $organization->getState()->getName()
            : '';
        $this->replacements['%ORGANIZATION_ZIP_CODE%'] = $organization->getZipCode() ?: '';
        $this->replacements['%ORGANIZATION_EMAIL%'] = $organization->getEmail() ?: '';
        $this->replacements['%ORGANIZATION_PHONE%'] = $organization->getPhone() ?: '';
        $this->replacements['%ORGANIZATION_WEBSITE%'] = $organization->getWebsite() ?: '';
    }

    public function setInvoice(Invoice $invoice): void
    {
        $this->replacements['%INVOICE_NUMBER%'] = $invoice->getInvoiceNumber();
        $this->replacements['%INVOICE_TOTAL%'] = $this->formatter->formatCurrency(
            $invoice->getTotal(),
            $invoice->getCurrency()->getCode(),
            $invoice->getOrganization()->getLocale()
        );
        $this->replacements['%INVOICE_TOTAL_AMOUNT_DUE%'] = $this->formatter->formatCurrency(
            $invoice->getAmountToPay(),
            $invoice->getCurrency()->getCode(),
            $invoice->getOrganization()->getLocale()
        );
        $this->replacements['%INVOICE_CREATED_DATE%'] = $this->formatter->formatDate(
            $invoice->getCreatedDate(),
            Formatter::ALTERNATIVE,
            Formatter::NONE
        );
        $this->replacements['%INVOICE_DUE_DATE%'] = $this->formatter->formatDate(
            $invoice->getDueDate(),
            Formatter::ALTERNATIVE,
            Formatter::NONE
        );
    }

    public function setOnlinePaymentLink(string $url): void
    {
        $this->replacements['%ONLINE_PAYMENT_LINK%'] = $url;
    }

    public function setQuote(Quote $quote): void
    {
        $this->replacements['%QUOTE_NUMBER%'] = $quote->getQuoteNumber();
        $this->replacements['%QUOTE_TOTAL%'] = $this->formatter->formatCurrency(
            $quote->getTotal(),
            $quote->getCurrency()->getCode(),
            $quote->getOrganization()->getLocale()
        );
        $this->replacements['%QUOTE_CREATED_DATE%'] = $this->formatter->formatDate(
            $quote->getCreatedDate(),
            Formatter::ALTERNATIVE,
            Formatter::NONE
        );
    }

    /**
     * @param Invoice[] $invoices
     */
    public function setInvoices(array $invoices): void
    {
        $invoiceNumber = [];
        $invoiceTotal = [];
        $invoiceCreatedDate = [];
        $invoiceDueDate = [];
        $invoiceAmountDue = [];

        foreach ($invoices as $invoice) {
            $currencyCode = $invoice->getCurrency()->getCode();
            $invoiceNumber[] = $invoice->getInvoiceNumber();
            $invoiceTotal[] = $this->formatter->formatCurrency(
                $invoice->getTotal(),
                $currencyCode,
                $invoice->getOrganization()->getLocale()
            );
            $invoiceAmountDue[] = $this->formatter->formatCurrency(
                $invoice->getAmountToPay(),
                $currencyCode,
                $invoice->getOrganization()->getLocale()
            );
            $invoiceCreatedDate[] = $this->formatter->formatDate(
                $invoice->getCreatedDate(),
                Formatter::ALTERNATIVE,
                Formatter::NONE
            );
            $invoiceDueDate[] = $this->formatter->formatDate(
                $invoice->getDueDate(),
                Formatter::ALTERNATIVE,
                Formatter::NONE
            );
        }

        $this->replacements['%INVOICE_NUMBER%'] = implode(', ', $invoiceNumber);
        $this->replacements['%INVOICE_TOTAL%'] = implode(', ', $invoiceTotal);
        $this->replacements['%INVOICE_CREATED_DATE%'] = implode(', ', $invoiceCreatedDate);
        $this->replacements['%INVOICE_DUE_DATE%'] = implode(', ', $invoiceDueDate);
        $this->replacements['%INVOICE_TOTAL_AMOUNT_DUE%'] = implode(', ', $invoiceAmountDue);
    }

    public function setService(Service $service): void
    {
        $this->replacements['%SERVICE_NAME%'] = $service->getName();
        $this->replacements['%SERVICE_TARIFF%'] = $service->getTariff()->getName();
        $this->replacements['%SERVICE_PRICE%'] = $service->getTariffPeriod()->getPrice();
        $this->replacements['%SERVICE_ACTIVE_FROM%'] = $service->getActiveFrom()
            ? $this->formatter->formatDate(
                $service->getActiveFrom(),
                Formatter::DEFAULT,
                Formatter::NONE
            )
            : '';
        $this->replacements['%SERVICE_ACTIVE_TO%'] = $service->getActiveTo()
            ? $this->formatter->formatDate(
                $service->getActiveTo(),
                Formatter::DEFAULT,
                Formatter::NONE
            )
            : '';
    }

    public function setClientFirstLoginUrl(string $url): void
    {
        $this->replacements['%CLIENT_FIRST_LOGIN_URL%'] = $url;
    }

    public function setClientResetPasswordUrl(string $url): void
    {
        $this->replacements['%CLIENT_RESET_PASSWORD_URL%'] = $url;
    }

    public function setTicket(Ticket $ticket): void
    {
        $this->replacements['%TICKET_ID%'] = $ticket->getId();
        $this->replacements['%TICKET_STATUS%'] = $this->translator->trans(Ticket::STATUSES[$ticket->getStatus()]);
        $this->replacements['%TICKET_SUBJECT%'] = $ticket->getSubject();
        $this->replacements['%TICKET_EMAIL_FOOTER%'] = $this->getTicketEmailFooter($ticket->getId());
    }

    public function setTicketMessage(string $message): void
    {
        $this->replacements['%TICKET_MESSAGE%'] = $message;
    }

    public function setTicketCommentAttachments(Collection $attachments): void
    {
        $this->replacements['%TICKET_COMMENT_ATTACHMENTS_COUNT%'] = $attachments->count();
    }

    public function setTicketUrl(string $url): void
    {
        $this->replacements['%TICKET_URL%'] = $url;
    }

    public function setPaymentPlan(PaymentPlan $paymentPlan): void
    {
        $this->replacements['%PAYMENT_PLAN_PROVIDER%'] = PaymentPlan::PROVIDER_NAMES[$paymentPlan->getProvider()] ?? '';
    }

    public function setPaymentPlanChange(PaymentPlan $newPaymentPlan, PaymentPlan $oldPaymentPlan): void
    {
        $currencyCode = $newPaymentPlan->getClient()->getCurrencyCode();

        $this->replacements['%PAYMENT_PLAN_OLD_AMOUNT%'] = $this->formatter->formatCurrency(
            round(
                $oldPaymentPlan->getAmountInSmallestUnit() / $oldPaymentPlan->getSmallestUnitMultiplier(),
                $oldPaymentPlan->getCurrency()->getFractionDigits()
            ),
            $currencyCode,
            $newPaymentPlan->getClient()->getOrganization()->getLocale()
        );
        $this->replacements['%PAYMENT_PLAN_NEW_AMOUNT%'] = $this->formatter->formatCurrency(
            round(
                $newPaymentPlan->getAmountInSmallestUnit() / $newPaymentPlan->getSmallestUnitMultiplier(),
                $oldPaymentPlan->getCurrency()->getFractionDigits()
            ),
            $currencyCode,
            $newPaymentPlan->getClient()->getOrganization()->getLocale()
        );
    }

    public function getSubject(): string
    {
        $replacements = $this->getReplacements(false);
        $subject = $this->subject;
        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);

        return $subject;
    }

    public function getBodyTemplate(): string
    {
        $replacements = $this->getReplacements();
        $template = $this->bodyTemplate;
        $template = str_replace(array_keys($replacements), array_values($replacements), $template);

        return $template;
    }

    public function getTicketEmailFooter(int $ticketId): string
    {
        return sprintf(
            '<hr><small>ucrm_ticket_identification#%s</small>',
            $ticketId
        );
    }

    public function getExtraCss(): string
    {
        return $this->extraCss;
    }

    public function setExtraCss(string $extraCss): void
    {
        $this->extraCss = $extraCss;
    }

    private function getReplacements(bool $htmlspecialchars = true): array
    {
        $escapedReplacements = [];
        foreach ($this->replacements as $key => $value) {
            if (! in_array($key, self::RAW_REPLACEMENTS, true)) {
                $value = nl2br(
                    $htmlspecialchars
                        ? htmlspecialchars((string) $value, ENT_QUOTES)
                        : (string) $value
                );
            } else {
                // If the replacement is raw, we don't want to keep the <p></p> around it.
                $escapedReplacements[sprintf('<p>%s</p>', $key)] = $value;
            }

            $escapedReplacements[$key] = $value;
        }

        return $escapedReplacements;
    }
}
