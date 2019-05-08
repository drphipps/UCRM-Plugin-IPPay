<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Payment;

use AppBundle\Entity\Client;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Entity\Payment;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Handler\Payment\PdfHandler;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Service\NotificationFactory;
use AppBundle\Service\Options;
use AppBundle\Service\Payment\PaymentReceiptTemplateParametersProvider;
use AppBundle\Service\Payment\PaymentReceiptTemplateRenderer;
use AppBundle\Util\Message;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings as NStrings;
use Symfony\Component\Templating\EngineInterface;

class ReceiptSender
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var EngineInterface
     */
    private $twigEngine;

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
     * @var PaymentReceiptTemplateParametersProvider
     */
    private $paymentReceiptTemplateParametersProvider;

    /**
     * @var PaymentReceiptTemplateRenderer
     */
    private $paymentReceiptTemplateRenderer;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    public function __construct(
        EntityManager $em,
        Options $options,
        EngineInterface $twigEngine,
        EmailEnqueuer $emailEnqueuer,
        EmailLogger $emailLogger,
        NotificationFactory $notificationFactory,
        PaymentReceiptTemplateParametersProvider $paymentReceiptTemplateParametersProvider,
        PaymentReceiptTemplateRenderer $paymentReceiptTemplateRenderer,
        PdfHandler $pdfHandler
    ) {
        $this->em = $em;
        $this->options = $options;
        $this->twigEngine = $twigEngine;
        $this->emailEnqueuer = $emailEnqueuer;
        $this->emailLogger = $emailLogger;
        $this->notificationFactory = $notificationFactory;
        $this->paymentReceiptTemplateParametersProvider = $paymentReceiptTemplateParametersProvider;
        $this->paymentReceiptTemplateRenderer = $paymentReceiptTemplateRenderer;
        $this->pdfHandler = $pdfHandler;
    }

    /**
     * @throws TemplateRenderException
     */
    public function send(Payment $payment): void
    {
        /** @var Payment $payment */
        $payment = $this->em->merge($payment);
        $this->em->refresh($payment);

        if (! $payment->getClient()) {
            return;
        }

        $notificationTemplate = $this->em->find(
            NotificationTemplate::class,
            NotificationTemplate::CLIENT_PAYMENT_RECEIPT
        );

        $client = $payment->getClient();
        assert($client instanceof Client);
        $organization = $client->getOrganization();

        $notification = $this->notificationFactory->create();
        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setBodyTemplate($notificationTemplate->getBody());
        $notification->setClient($client);
        $receiptTemplate = $organization->getPaymentReceiptTemplate();
        $notification->setExtraCss($this->paymentReceiptTemplateRenderer->getSanitizedCss($receiptTemplate));
        $notification->addReplacement('%PAYMENT_RECEIPT_PDF%', '');
        $billingEmails = $client->getBillingEmails();

        $message = new Message();

        $paymentReceiptHtml = $this->paymentReceiptTemplateRenderer->getPaymentReceiptHtml(
            $payment,
            $organization->getPaymentReceiptTemplate(),
            true
        );

        $images = $this->paymentReceiptTemplateParametersProvider->getImagesOrganization($payment);
        foreach ($images as $pattern => $imageData) {
            switch ($pattern) {
                case PaymentReceiptTemplateParametersProvider::PARAM_ORGANIZATION_LOGO:
                    $filename = sprintf('thumb_%s', $organization->getLogo());
                    break;
                case PaymentReceiptTemplateParametersProvider::PARAM_ORGANIZATION_LOGO_ORIGINAL:
                    $filename = $organization->getLogo();
                    break;
                case PaymentReceiptTemplateParametersProvider::PARAM_ORGANIZATION_STAMP:
                    $filename = sprintf('thumb_%s', $organization->getLogo());
                    break;
                case PaymentReceiptTemplateParametersProvider::PARAM_ORGANIZATION_STAMP_ORIGINAL:
                    $filename = $organization->getLogo();
                    break;
                default:
                    $filename = null;
            }

            if (NStrings::contains($paymentReceiptHtml, $pattern)) {
                $paymentReceiptHtml = NStrings::replace(
                    $paymentReceiptHtml,
                    '~' . preg_quote($pattern, '~') . '~',
                    $message->embed(
                        new \Swift_Image(
                            $imageData,
                            $filename,
                            (new \finfo(FILEINFO_MIME_TYPE))->buffer($imageData)
                        )
                    )
                );
            }
        }

        $notification->addReplacement('%PAYMENT_RECEIPT%', $paymentReceiptHtml);

        $message->setClient($client);
        $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS) ?: $organization->getEmail() ?: null);
        $message->setSubject($notification->getSubject());
        $message->setFrom($organization->getEmail(), $organization->getName());
        $message->setTo($billingEmails);
        $message->setBody(
            $this->twigEngine->render(
                'email/client/plain.html.twig',
                [
                    'body' => $notification->getBodyTemplate(),
                    'extraCss' => $notification->getExtraCss(),
                ]
            ),
            'text/html'
        );

        if (NStrings::contains($notificationTemplate->getBody(), '%PAYMENT_RECEIPT_PDF%')) {
            $path = $this->pdfHandler->getFullPaymentReceiptPdfPath($payment);
            if ($path) {
                $message->attach(\Swift_Attachment::fromPath($path));
            }
        }

        $payment->setSendReceipt(false);

        if (! $billingEmails) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because client %clientName% (ID: %clientId%) has no email set.',
                EmailLog::STATUS_ERROR,
                ['%clientName%' => $client->getNameForView(), '%clientId%' => $client->getId()]
            );
            $this->em->flush($payment);

            return;
        }

        $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_MEDIUM);
        $payment->setReceiptSentDate(new \DateTime());
        $this->em->flush($payment);
    }
}
