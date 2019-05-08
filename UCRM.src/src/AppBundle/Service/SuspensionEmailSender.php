<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\EmailLog;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Handler\Invoice\PdfHandler;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Util\Message;
use Doctrine\ORM\EntityManagerInterface;

class SuspensionEmailSender
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
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var EmailEnqueuer
     */
    private $emailEnqueuer;

    /**
     * @var EmailLogger
     */
    private $emailLogger;

    /**
     * @var NotificationFactory
     */
    private $notificationFactory;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        \Twig_Environment $twig,
        EmailEnqueuer $emailEnqueuer,
        EmailLogger $emailLogger,
        NotificationFactory $notificationFactory,
        PdfHandler $pdfHandler
    ) {
        $this->entityManager = $entityManager;
        $this->options = $options;
        $this->twig = $twig;
        $this->emailEnqueuer = $emailEnqueuer;
        $this->emailLogger = $emailLogger;
        $this->notificationFactory = $notificationFactory;
        $this->pdfHandler = $pdfHandler;
    }

    /**
     * @param Invoice[] $invoices
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function send(Service $service, array $invoices): void
    {
        $notificationTemplate = $this->entityManager->getRepository(NotificationTemplate::class)
            ->find(NotificationTemplate::CLIENT_SUSPEND_SERVICE);

        $message = $this->createMessage($service, $invoices, $notificationTemplate);
        if (! $message) {
            return;
        }

        $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_LOW);
    }

    /**
     * @param Invoice[] $invoices
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function createMessage(Service $service, array $invoices, NotificationTemplate $notificationTemplate): ?Message
    {
        $client = $service->getClient();
        $message = new Message();
        if (! $client) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because no client is set.',
                EmailLog::STATUS_ERROR
            );

            return null;
        }
        $organization = $client->getOrganization();
        if (! $organization) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because no organization is set for client.',
                EmailLog::STATUS_ERROR
            );

            return null;
        }
        $notification = $this->notificationFactory->create();
        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setBodyTemplate($notificationTemplate->getBody());
        $notification->setClient($client);
        $notification->setInvoices($invoices);
        $notification->setService($service);

        $billingEmails = $client->getBillingEmails();

        $message->setClient($client);
        $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS) ?: $organization->getEmail() ?: null);
        $message->setSubject($notification->getSubject());
        $message->setFrom($organization->getEmail(), $organization->getName());
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

        foreach ($invoices as $invoice) {
            $path = $this->pdfHandler->getFullInvoicePdfPath($invoice);
            if (! $path) {
                continue;
            }

            $message->attach(\Swift_Attachment::fromPath($path));
        }

        if (! $billingEmails) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because client %clientName% (ID: %clientId%) has no email set.',
                EmailLog::STATUS_ERROR,
                ['%clientName%' => $client->getNameForView(), '%clientId%' => $client->getId()]
            );

            return null;
        }

        return $message;
    }
}
