<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\AuthorizeNet;
use AppBundle\Component\IpPay\Exception\FailedPaymentException;
use AppBundle\Component\IpPay\IpPayRequestSender;
use AppBundle\Component\MercadoPago;
use AppBundle\Component\PayPal;
use AppBundle\Component\Stripe;
use AppBundle\DataProvider\CertificateDataProvider;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Exception\ImapConnectionException;
use AppBundle\Security\Permission;
use AppBundle\Service\Mailer\OptionsAwareTransport;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Elastica\Status;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use TicketingBundle\DataProvider\TicketImapInboxDataProvider;
use TicketingBundle\Entity\TicketImapInbox;
use TicketingBundle\Service\Factory\TicketImapModelFactory;

class SystemStatusController extends BaseController
{
    /**
     * @Route("/check-mailer", name="system_status_check_mailer", options={"expose"=true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function checkMailerAction(): JsonResponse
    {
        $this->preventSessionBlocking();

        if (
            (
                $this->getOption(Option::MAILER_TRANSPORT) === Option::MAILER_TRANSPORT_SMTP
                && ! $this->getOption(Option::MAILER_HOST)
            )
            || (
                $this->getOption(Option::MAILER_TRANSPORT) === Option::MAILER_TRANSPORT_GMAIL
                && ! $this->getOption(Option::MAILER_USERNAME)
            )
        ) {
            return new JsonResponse(
                [
                    'status' => 'failed',
                    'html' => $this->renderView('homepage/components/system_status/mailer_not_configured.html.twig'),
                ]
            );
        }

        $mailerTransport = $this->get('mailer')->getTransport();
        if (method_exists($mailerTransport, 'setTimeout')) {
            $mailerTransport->setTimeout(1);
        }

        $message = null;
        try {
            if ($mailerTransport instanceof OptionsAwareTransport) {
                $mailerTransport->realStart();
            } else {
                $mailerTransport->start();
            }
            $mailerOk = $mailerTransport->isStarted();
        } catch (\Exception $e) {
            $mailerOk = false;

            $message = $e instanceof WrongKeyOrModifiedCiphertextException
                ? $this->trans('Mailer password could not be decrypted - wrong encryption key.')
                : $e->getMessage();
        }

        if (! $mailerOk) {
            return new JsonResponse(
                [
                    'status' => 'failed',
                    'message' => $message ?: $this->trans('Connection failed.'),
                    'html' => $this->renderView('homepage/components/system_status/mailer_failed.html.twig'),
                ]
            );
        }

        return new JsonResponse(
            [
                'status' => 'ok',
                'message' => $this->trans('Mailer connection OK.'),
            ]
        );
    }

    /**
     * @Route("/check-imap-inboxes", name="system_status_check_imap_inboxes", options={"expose"=true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function checkImapServerAction(): JsonResponse
    {
        $this->preventSessionBlocking();

        $ticketImapInboxes = $this->get(TicketImapInboxDataProvider::class)->findAll();

        if (! $ticketImapInboxes) {
            return new JsonResponse(
                [
                    'status' => 'skipped',
                ]
            );
        }

        $message = null;
        $imapOk = false;
        try {
            foreach ($ticketImapInboxes as $ticketImapInbox) {
                $imapOk = $this->get(TicketImapModelFactory::class)->create($ticketImapInbox)->checkConnection();
                if (! $imapOk) {
                    break;
                }
            }
        } catch (EnvironmentIsBrokenException $exception) {
            $message = $this->trans('Password could not be decrypted - wrong environment.');
        } catch (WrongKeyOrModifiedCiphertextException $exception) {
            $message = $this->trans('Password could not be decrypted - wrong encryption key.');
        } catch (ImapConnectionException | AuthenticationFailedException $exception) {
            $message = $exception->getMessage();
        }

        if (! $imapOk) {
            return new JsonResponse(
                [
                    'status' => 'failed',
                    'message' => $message ?: $this->trans('Connection failed.'),
                    'html' => $this->renderView(
                        'homepage/components/system_status/imap_server_failed.html.twig',
                        [
                            'message' => $message ?: false,
                        ]
                    ),
                ]
            );
        }

        return new JsonResponse(
            [
                'status' => 'ok',
                'message' => $this->trans('IMAP connection OK.'),
            ]
        );
    }

    /**
     * @Route("/check-imap-inbox/{id}", name="system_status_check_imap_inbox", options={"expose"=true}, requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function checkImapInboxAction(TicketImapInbox $ticketImapInbox): JsonResponse
    {
        $this->preventSessionBlocking();

        $message = null;
        $imapOk = false;
        try {
            $imapOk = $this->get(TicketImapModelFactory::class)->create($ticketImapInbox)->checkConnection();
        } catch (EnvironmentIsBrokenException $exception) {
            $message = $this->trans('Password could not be decrypted - wrong environment.');
        } catch (WrongKeyOrModifiedCiphertextException $exception) {
            $message = $this->trans('Password could not be decrypted - wrong encryption key.');
        } catch (ImapConnectionException | AuthenticationFailedException $exception) {
            $message = $exception->getMessage();
        }

        if (! $imapOk) {
            return new JsonResponse(
                [
                    'status' => 'failed',
                    'message' => $message ?: $this->trans('Connection failed.'),
                    'html' => $this->renderView(
                        'homepage/components/system_status/imap_server_failed.html.twig',
                        [
                            'message' => $message ?: false,
                        ]
                    ),
                ]
            );
        }

        return new JsonResponse(
            [
                'status' => 'ok',
                'message' => $this->trans('IMAP connection OK.'),
            ]
        );
    }

    /**
     * @Route("/check-elasticsearch", name="system_status_check_elasticsearch", options={"expose"=true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function checkElasticsearchAction(): JsonResponse
    {
        $this->preventSessionBlocking();

        $data = (new Status($this->get('fos_elastica.client.default')))->getData();

        if (! array_key_exists('indices', $data)) {
            return new JsonResponse(
                [
                    'status' => 'failed',
                    'html' => $this->renderView('homepage/components/system_status/elasticsearch_failed.html.twig'),
                ]
            );
        }

        return new JsonResponse(
            [
                'status' => 'ok',
            ]
        );
    }

    /**
     * @Route("/check-certificate", name="system_status_check_certificate", options={"expose"=true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function checkCertificateAction(): JsonResponse
    {
        $this->preventSessionBlocking();

        $certificateDataProvider = $this->get(CertificateDataProvider::class);
        $expiration = [
            'expired' => false,
            'date' => null,
        ];

        if ($certificateDataProvider->isCustomEnabled()) {
            $expirationDate = $certificateDataProvider->getCustomExpiration();
            if ($expirationDate <= new \DateTimeImmutable()) {
                $expiration['date'] = $expirationDate;
                $expiration['expired'] = true;
            } elseif ($expirationDate <= new \DateTimeImmutable('+1 month')) {
                $expiration['date'] = $expirationDate;
            }
        } elseif ($certificateDataProvider->isLetsEncryptEnabled()) {
            $expirationDate = $certificateDataProvider->getLetsEncryptExpiration();
            if ($expirationDate <= new \DateTimeImmutable()) {
                $expiration['date'] = $expirationDate;
                $expiration['expired'] = true;
            } elseif ($expirationDate <= new \DateTimeImmutable('+7 days')) {
                $expiration['date'] = $expirationDate;
            }
        }

        if ($expiration['date']) {
            return new JsonResponse(
                [
                    'status' => 'failed',
                    'html' => $this->renderView(
                        'homepage/components/system_status/certificate_expiring.html.twig',
                        [
                            'expiration' => $expiration,
                        ]
                    ),
                ]
            );
        }

        return new JsonResponse(
            [
                'status' => 'ok',
            ]
        );
    }

    /**
     * @Route("/check-payment-providers", name="system_status_check_payment_providers", options={"expose"=true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function checkPaymentProvidersAction(): JsonResponse
    {
        $this->preventSessionBlocking();

        $organization = $this->em->getRepository(Organization::class)->getFirstSelected();
        if (! $organization) {
            return new JsonResponse(
                [
                    'status' => 'ok',
                ]
            );
        }
        $sandbox = $this->isSandbox();
        $errors = [];

        if ($organization->getPayPalClientId($sandbox) && $organization->getPayPalClientSecret($sandbox)) {
            $errors[] = $this->checkPaypal($organization, $sandbox);
        }

        // Only secret key can be verified via API request for Stripe.
        if ($organization->getStripeSecretKey($sandbox)) {
            $errors[] = $this->checkStripe($organization, $sandbox);
        }

        if ($organization->getAnetLoginId($sandbox) && $organization->getAnetTransactionKey($sandbox)) {
            $errors[] = $this->checkAnet($organization, $sandbox);
            $errors[] = $this->checkAnetSignatureKey($organization, $sandbox);
        }

        if ($organization->getIpPayUrl($sandbox) && $organization->getIpPayTerminalId($sandbox)) {
            $errors[] = $this->checkIpPay($organization);
        }

        if ($organization->getMercadoPagoClientId() && $organization->getMercadoPagoClientSecret()) {
            $errors[] = $this->checkMercadoPago($organization);
        }

        $errors = array_filter($errors);
        if ($errors) {
            return new JsonResponse(
                [
                    'status' => 'failed',
                    'html' => implode('', $errors),
                ]
            );
        }

        return new JsonResponse(
            [
                'status' => 'ok',
            ]
        );
    }

    /**
     * @Route("/check-templates", name="system_status_check_templates", options={"expose"=true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function checkTemplatesAction(): JsonResponse
    {
        $this->preventSessionBlocking();

        $organizations = $this->em->getRepository(Organization::class)->findAll();
        if (! $organizations) {
            return new JsonResponse(
                [
                    'status' => 'ok',
                ]
            );
        }

        $invalidInvoiceTemplates = [];
        $invalidReceiptTemplates = [];
        foreach ($organizations as $organization) {
            $invoiceTemplate = $organization->getInvoiceTemplate();
            if ($invoiceTemplate && ! $invoiceTemplate->getIsValid()) {
                $invalidInvoiceTemplates[] = $invoiceTemplate;
            }

            $receiptTemplate = $organization->getPaymentReceiptTemplate();
            if ($receiptTemplate && ! $receiptTemplate->getIsValid()) {
                $invalidReceiptTemplates[] = $receiptTemplate;
            }
        }

        if ($invalidInvoiceTemplates || $invalidReceiptTemplates) {
            return new JsonResponse(
                [
                    'status' => 'failed',
                    'html' => $this->renderView(
                        'homepage/components/system_status/template_invalid.html.twig',
                        [
                            'invalidInvoiceTemplates' => $invalidInvoiceTemplates,
                            'invalidReceiptTemplates' => $invalidReceiptTemplates,
                        ]
                    ),
                ]
            );
        }

        return new JsonResponse(
            [
                'status' => 'ok',
            ]
        );
    }

    private function checkPaypal(Organization $organization, bool $sandbox): ?string
    {
        try {
            $apiContext = $this->get(PayPal\ApiContextFactory::class)->create($organization, $sandbox);
            $this->get(PayPal\ConnectionCheck::class)->check($apiContext);
        } catch (PayPal\PayPalException $exception) {
            return $this->renderView(
                'homepage/components/system_status/paypal_failed.html.twig',
                [
                    'message' => $exception->getMessage(),
                ]
            );
        }

        return null;
    }

    private function checkStripe(Organization $organization, bool $sandbox): ?string
    {
        try {
            $this->get(Stripe\ConnectionCheck::class)->check($organization, $sandbox);
        } catch (Stripe\Exception\StripeException $exception) {
            return $this->renderView(
                'homepage/components/system_status/stripe_failed.html.twig',
                [
                    'message' => $exception->getMessage(),
                ]
            );
        }

        return null;
    }

    private function checkAnet(Organization $organization, bool $sandbox): ?string
    {
        try {
            $connectionCheck = $this->get(AuthorizeNet\ConnectionCheckFactory::class)->create($organization, $sandbox);
            $connectionCheck->check();
        } catch (AuthorizeNet\AuthorizeNetException $exception) {
            return $this->renderView(
                'homepage/components/system_status/anet_failed.html.twig',
                [
                    'message' => $exception->getMessage(),
                ]
            );
        }

        return null;
    }

    private function checkAnetSignatureKey(Organization $organization, bool $sandbox): ?string
    {
        return $organization->getAnetSignatureKey($sandbox)
            ? null
            : $this->renderView(
                'homepage/components/system_status/anet_signature_key.html.twig'
            );
    }

    private function checkIpPay(Organization $organization): ?string
    {
        try {
            $this->get(IpPayRequestSender::class)->sendPingRequest($organization);
        } catch (FailedPaymentException $exception) {
            return $this->renderView(
                'homepage/components/system_status/ippay_failed.html.twig',
                [
                    'message' => $this->trans(
                        $exception->getMessage(),
                        $exception->getErrorCode() ? ['%code%' => $exception->getErrorCode()] : []
                    ),
                ]
            );
        }

        return null;
    }

    private function checkMercadoPago(Organization $organization): ?string
    {
        try {
            $this->get(MercadoPago\ConnectionCheck::class)->check($organization);
        } catch (\MercadoPagoException $exception) {
            return $this->renderView(
                'homepage/components/system_status/mercado_pago_failed.html.twig',
                [
                    'message' => $exception->getMessage(),
                ]
            );
        }

        return null;
    }
}
