<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Financial;

use AppBundle\Entity\Client;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Facade\OnlinePaymentFacade;
use AppBundle\Factory\Financial\PaymentTokenFactory;
use AppBundle\Handler\Invoice\PdfHandler as InvoicePdfHandler;
use AppBundle\Handler\Quote\PdfHandler as QuotePdfHandler;
use AppBundle\Service\Client\ClientAccountStandingsCalculator;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Service\NotificationFactory;
use AppBundle\Service\Options;
use AppBundle\Service\PublicUrlGenerator;
use AppBundle\Util\Message;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class FinancialEmailSender
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EmailEnqueuer
     */
    private $emailEnqueuer;

    /**
     * @var EmailLogger
     */
    private $emailLogger;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var NotificationFactory
     */
    private $notificationFactory;

    /**
     * @var EntityRepository
     */
    private $notificationTemplateRepository;

    /**
     * @var InvoicePdfHandler
     */
    private $invoicePdfHandler;

    /**
     * @var QuotePdfHandler
     */
    private $quotePdfHandler;

    /**
     * @var ClientAccountStandingsCalculator
     */
    private $clientAccountStandingsCalculator;

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

    public function __construct(
        EntityManager $entityManager,
        EmailEnqueuer $emailEnqueuer,
        EmailLogger $emailLogger,
        Options $options,
        \Twig_Environment $twig,
        NotificationFactory $notificationFactory,
        InvoicePdfHandler $invoicePdfHandler,
        QuotePdfHandler $quotePdfHandler,
        ClientAccountStandingsCalculator $clientAccountStandingsCalculator,
        PublicUrlGenerator $publicUrlGenerator,
        OnlinePaymentFacade $onlinePaymentFacade,
        PaymentTokenFactory $paymentTokenFactory
    ) {
        $this->entityManager = $entityManager;
        $this->emailEnqueuer = $emailEnqueuer;
        $this->emailLogger = $emailLogger;
        $this->twig = $twig;
        $this->notificationFactory = $notificationFactory;
        $this->options = $options;
        $this->notificationTemplateRepository = $this->entityManager->getRepository(NotificationTemplate::class);
        $this->invoicePdfHandler = $invoicePdfHandler;
        $this->quotePdfHandler = $quotePdfHandler;
        $this->clientAccountStandingsCalculator = $clientAccountStandingsCalculator;
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->onlinePaymentFacade = $onlinePaymentFacade;
        $this->paymentTokenFactory = $paymentTokenFactory;
    }

    public function send(FinancialInterface $financial, int $notificationType): void
    {
        $notificationTemplate = $this->notificationTemplateRepository->findOneBy(
            [
                'type' => $notificationType,
            ]
        );
        $this->entityManager->refresh($financial);
        $client = $financial->getClient();
        assert($client instanceof Client);
        $this->clientAccountStandingsCalculator->calculate($client);

        $notification = $this->notificationFactory->create();
        $notification->setSubject($notificationTemplate->getSubject() ?? '');
        $notification->setBodyTemplate($notificationTemplate->getBody() ?? '');
        $notification->setClient($client);

        $billingEmails = $client->getBillingEmails();
        $message = new Message();
        $message->setClient($client);

        if ($financial instanceof Invoice) {
            $notification->setInvoice($financial);
            try {
                $token = $financial->getPaymentToken();
                if (! $token) {
                    $token = $this->paymentTokenFactory->create($financial);
                    $this->onlinePaymentFacade->handleCreatePaymentToken($token);
                }

                $url = $this->publicUrlGenerator->generate(
                    'online_payment_pay',
                    [
                        'token' => $token->getToken(),
                    ]
                );
            } catch (PublicUrlGeneratorException $e) {
                $url = '';
            }
            $notification->setOnlinePaymentLink($url);

            $message->setInvoice($financial);

            $path = $this->invoicePdfHandler->getFullInvoicePdfPath($financial);
            if ($path) {
                $message->attach(\Swift_Attachment::fromPath($path));
            }
        } elseif ($financial instanceof Quote) {
            $notification->setQuote($financial);
            $message->setQuote($financial);

            $path = $this->quotePdfHandler->getFullQuotePdfPath($financial);
            if ($path) {
                $message->attach(\Swift_Attachment::fromPath($path));
            }
        }

        $message->setSubject($notification->getSubject());
        $message->setFrom($client->getOrganization()->getEmail(), $client->getOrganization()->getName());
        $message->setSender(
            $this->options->get(Option::MAILER_SENDER_ADDRESS)
                ?: $client->getOrganization()->getEmail()
                ?: null
        );
        $message->setTo($billingEmails);
        $message->setBody(
            $this->twig->render(
                'email/client/plain.html.twig',
                [
                    'body' => $notification->getBodyTemplate(),
                ]
            ),
            'text/html'
        );

        if (! $billingEmails) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because client %clientName% (ID: %clientId%) has no email set.',
                EmailLog::STATUS_ERROR,
                ['%clientName%' => $client->getNameForView(), '%clientId%' => $client->getId()]
            );
            if ($financial instanceof Invoice) {
                $financial->setOverdueNotificationSent(false);
            }

            return;
        }

        $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_LOW);
        $financial->setEmailSentDate(new \DateTime());
        $financial->setClientEmail(reset($billingEmails));
        $this->entityManager->flush($financial);
    }
}
