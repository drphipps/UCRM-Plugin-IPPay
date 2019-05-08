<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Entity\UserAuthenticationKey;
use AppBundle\Component\Command\Statistics\UcrmStatisticsData;
use AppBundle\Entity\AppKey;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Plugin;
use AppBundle\Entity\Service;
use AppBundle\Entity\Shortcut;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\User;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use SchedulingBundle\Entity\Job;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketImapInbox;

class UcrmStatisticsDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var CertificateDataProvider
     */
    private $certificateDataProvider;

    /**
     * @var ClientDataProvider
     */
    private $clientDataProvider;

    /**
     * @var string
     */
    private $version;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        CertificateDataProvider $certificateDataProvider,
        ClientDataProvider $clientDataProvider,
        string $version
    ) {
        $this->entityManager = $entityManager;
        $this->options = $options;
        $this->certificateDataProvider = $certificateDataProvider;
        $this->clientDataProvider = $clientDataProvider;
        $this->version = $version;
    }

    public function getData(?string $token): UcrmStatisticsData
    {
        $data = new UcrmStatisticsData();
        $data->token = $token;
        $data->version = $this->version;
        $lastLogin = $this->entityManager->getRepository(User::class)->getMaxLastLogin();
        $data->lastLogin = $lastLogin ? $lastLogin->format(\DateTime::ISO8601) : null;
        $data->uasInstallation = $this->options->getGeneral(General::UAS_INSTALLATION);

        $organizations = $this->entityManager->getRepository(Organization::class)->findAll();

        $this->fillCounts($data);
        $this->fillPaymentGateways($data, $organizations);
        $this->fillMailer($data);
        $this->fillTicketing($data);
        $this->fillScheduling($data);
        $this->fillInvoicingSettings($data, $organizations);
        $this->fillSuspension($data);
        $this->fillFees($data);
        $this->fillLocalization($data);
        $this->fillTwoFactorAuthentication($data);
        $this->fillAppKeys($data);
        $this->fillSsl($data);
        $this->fillGeneral($data);
        $this->fillShortcuts($data);
        $this->fillPlugins($data);

        return $data;
    }

    private function fillCounts(UcrmStatisticsData $data): void
    {
        if (! $this->options->get(Option::SEND_ANONYMOUS_STATISTICS)) {
            return;
        }

        $data->counts['invoices'] = $this->entityManager->getRepository(Invoice::class)->getCount();
        $data->counts['clients'] = $this->clientDataProvider->getActiveCount();
        $data->counts['leads'] = $this->clientDataProvider->getLeadCount();
        $data->counts['organizations'] = $this->entityManager->getRepository(Organization::class)->getCount();
        $data->counts['jobs'] = $this->entityManager->getRepository(Job::class)->getCount();
        $data->counts['tickets'] = $this->entityManager->getRepository(Ticket::class)->getCount();
        $data->counts['admins'] = $this->entityManager->getRepository(User::class)->getAdminCount();
    }

    /**
     * @param Organization[] $organizations
     */
    private function fillPaymentGateways(UcrmStatisticsData $data, array $organizations): void
    {
        $data->paymentGateways['recurringEnabled'] = $this->options->get(Option::SUBSCRIPTIONS_ENABLED_CUSTOM);
        $data->paymentGateways['autopayEnabled'] = $this->options->get(Option::SUBSCRIPTIONS_ENABLED_LINKED);

        $data->paymentGateways['listActive'] = [];
        $sandbox = (bool) $this->options->getGeneral(General::SANDBOX_MODE);
        foreach ($organizations as $organization) {
            if ($organization->hasPayPal($sandbox)) {
                $data->paymentGateways['listActive'][] = 'PayPal';
            }

            if ($organization->hasStripe($sandbox)) {
                $data->paymentGateways['listActive'][] = 'Stripe';
            }

            if ($organization->hasStripeAch($sandbox)) {
                $data->paymentGateways['listActive'][] = 'Stripe ACH';
            }

            if ($organization->hasAuthorizeNet($sandbox)) {
                $data->paymentGateways['listActive'][] = 'Authorize.Net';
            }

            if ($organization->hasIpPay($sandbox)) {
                $data->paymentGateways['listActive'][] = 'IpPay';
            }

            if ($organization->hasMercadoPago()) {
                $data->paymentGateways['listActive'][] = 'MercadoPago';
            }
        }

        $data->paymentGateways['listActive'] = array_unique($data->paymentGateways['listActive']);
    }

    private function fillMailer(UcrmStatisticsData $data): void
    {
        $data->mailer['type'] = $this->options->get(Option::MAILER_TRANSPORT);
    }

    private function fillTicketing(UcrmStatisticsData $data): void
    {
        $data->ticketing['enabled'] = $this->options->get(Option::TICKETING_ENABLED);
        $data->ticketing['imapEnabled'] = $this->entityManager->getRepository(TicketImapInbox::class)->exists();
    }

    private function fillScheduling(UcrmStatisticsData $data): void
    {
        $data->scheduling['googleCalendarSyncEnabled'] = $this->entityManager->getRepository(User::class)
            ->existsAdminWithEnabledGoogleCalendarSynchronization();
    }

    /**
     * @param Organization[] $organizations
     */
    private function fillInvoicingSettings(UcrmStatisticsData $data, array $organizations): void
    {
        $data->invoicingSettings['pricingMode'] = Option::PRICING_MODES[$this->options->get(Option::PRICING_MODE)];
        $data->invoicingSettings['periodType'] =
            Service::INVOICING_PERIOD_TYPE[$this->options->get(Option::INVOICING_PERIOD_TYPE)];
        $data->invoicingSettings['periodTypeBackwardAverage'] =
            $this->entityManager->getRepository(Service::class)->getPeriodTypeBackwardAveragePercentage();
        $data->invoicingSettings['periodStartDay'] = $this->options->get(Option::INVOICE_PERIOD_START_DAY);
        $data->invoicingSettings['automaticDraftApprovalEnabled'] = $this->options->get(Option::SEND_INVOICE_BY_EMAIL);
        $data->invoicingSettings['automaticDraftApprovalEnabledServiceAverage'] =
            $this->entityManager->getRepository(Service::class)->getSendEmailsAutomaticallyAveragePercentage(
                $this->options->get(Option::SEND_INVOICE_BY_EMAIL)
            );
        $data->invoicingSettings['generateProformaInvoicesEnabled'] = $this->options->get(
            Option::GENERATE_PROFORMA_INVOICES
        );
        $data->invoicingSettings['generateProformaInvoicesEnabledClientAverage'] =
            $this->entityManager->getRepository(Client::class)->getGenerateProformaInvoicesAveragePercentage(
                $this->options->get(Option::GENERATE_PROFORMA_INVOICES)
            );

        $data->invoicingSettings['customInvoiceTemplateUsed'] = false;
        foreach ($organizations as $organization) {
            if (null === $organization->getInvoiceTemplate()->getOfficialName()) {
                $data->invoicingSettings['customInvoiceTemplateUsed'] = true;

                break;
            }
        }
    }

    private function fillSuspension(UcrmStatisticsData $data): void
    {
        $data->suspension['enabled'] = $this->options->get(Option::SUSPEND_ENABLED);
        $data->suspension['postponeEnabled'] = $this->options->get(Option::SUSPENSION_ENABLE_POSTPONE);
    }

    private function fillFees(UcrmStatisticsData $data): void
    {
        $data->fees['lateFeeEnabled'] = $this->options->get(Option::LATE_FEE_ACTIVE);

        $tariffRepository = $this->entityManager->getRepository(Tariff::class);
        $data->fees['setupFeeEnabled'] = $tariffRepository->existsTariffWithEnabledSetupFee();
        $data->fees['earlyTerminationFeeEnabled'] = $tariffRepository->existsTariffWithEnabledEarlyTerminationFee();
    }

    private function fillLocalization(UcrmStatisticsData $data): void
    {
        $data->localization['systemLanguage'] = $this->options->get(Option::APP_LOCALE);
        $data->localization['timezone'] = $this->options->get(Option::APP_TIMEZONE);
    }

    private function fillTwoFactorAuthentication(UcrmStatisticsData $data): void
    {
        $data->twoFactorAuthentication['enabled'] = $this->entityManager->getRepository(User::class)
            ->existsAdminWithEnabledTwoFactorAuthentication();
    }

    private function fillAppKeys(UcrmStatisticsData $data): void
    {
        if (! $this->options->get(Option::SEND_ANONYMOUS_STATISTICS)) {
            return;
        }

        $utcTimezone = new \DateTimeZone('UTC');

        $appKey = $this->entityManager->getRepository(AppKey::class)->getOneByLastUsedDate();
        $data->appKeys['exist'] = (bool) $appKey;
        if ($appKey && $appKey->getLastUsedDate()) {
            $lastUsedDate = (clone $appKey->getLastUsedDate())->setTimezone($utcTimezone);
            $data->appKeys['lastUsedDate'] = $lastUsedDate->format(\DateTime::ISO8601);
        }

        $userAuthenticationKey = $this->entityManager->getRepository(UserAuthenticationKey::class)
            ->getOneByLastUsedDate();
        $data->appKeys['mobileExist'] = (bool) $userAuthenticationKey;
        if ($userAuthenticationKey && $userAuthenticationKey->getLastUsedDate()) {
            $lastUsedDate = (clone $userAuthenticationKey->getLastUsedDate())->setTimezone($utcTimezone);
            $data->appKeys['mobileLastUsedDate'] = $lastUsedDate->format(\DateTime::ISO8601);
        }
    }

    private function fillSsl(UcrmStatisticsData $data): void
    {
        if ($this->certificateDataProvider->isLetsEncryptEnabled()) {
            $data->ssl['enabled'] = true;
            $data->ssl['certType'] = 'lets_encrypt';
        } elseif ($this->certificateDataProvider->isCustomEnabled()) {
            $data->ssl['enabled'] = true;
            $data->ssl['certType'] = 'custom';
        } else {
            $data->ssl['enabled'] = false;
        }
    }

    private function fillGeneral(UcrmStatisticsData $data): void
    {
        $data->general['sandboxEnabled'] = (bool) $this->options->getGeneral(General::SANDBOX_MODE);
        $data->general['errorReportingEnabled'] = $this->options->get(Option::ERROR_REPORTING);

        if ($this->options->get(Option::GOOGLE_API_KEY)) {
            $data->general['mapsProvider'] = 'google';
        } elseif ($this->options->get(Option::MAPBOX_TOKEN)) {
            $data->general['mapsProvider'] = 'mapbox';
        }

        $data->general['hasInvoices'] =
            ($data->counts['invoices'] ?? $this->entityManager->getRepository(Invoice::class)->getCount()) > 50;

        $data->general['hasClients'] =
            ($data->counts['clients'] ?? $this->entityManager->getRepository(Client::class)->getCount()) > 50;
    }

    private function fillShortcuts(UcrmStatisticsData $data): void
    {
        $list = [];
        $shortcuts = $this->entityManager->getRepository(Shortcut::class)->findAll();
        foreach ($shortcuts as $shortcut) {
            if (! array_key_exists($shortcut->getRoute(), $list)) {
                $list[$shortcut->getRoute()] = 1;
            } else {
                ++$list[$shortcut->getRoute()];
            }
        }

        $data->shortcuts['list'] = $list;
    }

    private function fillPlugins(UcrmStatisticsData $data): void
    {
        $list = [];
        $plugins = $this->entityManager->getRepository(Plugin::class)->findAll();
        foreach ($plugins as $plugin) {
            $list[$plugin->getName()] = $plugin->isEnabled();
        }

        $data->plugins['list'] = $list;
    }
}
