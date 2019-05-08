<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Payment;

use AppBundle\Component\HeaderNotification\HeaderNotifier;
use AppBundle\Component\Payment\ReceiptSender;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Payment;
use AppBundle\Event\Payment\PaymentEditEvent;
use AppBundle\Exception\TemplateRenderException;
use Ds\Queue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class SendReceiptSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|Payment[]
     */
    private $paymentQueue;

    /**
     * @var ReceiptSender
     */
    private $receiptSender;

    /**
     * @var HeaderNotifier
     */
    private $headerNotifier;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ReceiptSender $receiptSender,
        HeaderNotifier $headerNotifier,
        TranslatorInterface $translator,
        RouterInterface $router,
        LoggerInterface $logger
    ) {
        $this->paymentQueue = new Queue();
        $this->receiptSender = $receiptSender;
        $this->headerNotifier = $headerNotifier;
        $this->translator = $translator;
        $this->router = $router;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentEditEvent::class => 'handlePaymentEditEvent',
        ];
    }

    public function handlePaymentEditEvent(PaymentEditEvent $event): void
    {
        $this->paymentQueue->push($event->getPayment());
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        foreach ($this->paymentQueue as $payment) {
            if ($this->isPossibleToSend($payment)) {
                try {
                    $this->receiptSender->send($payment);
                } catch (TemplateRenderException $exception) {
                    $this->logger->log('error', 'Receipt has not been sent. Receipt template is invalid.');
                    $this->headerNotifier->sendToAllAdmins(
                        HeaderNotification::TYPE_WARNING,
                        $this->translator->trans(
                            'Receipt for payment ID %paymentId% has not been sent.',
                            ['%paymentId%' => $payment->getId()]
                        ),
                        $this->translator->trans(
                            'Receipt has not been sent. Receipt template is invalid.'
                        ),
                        $this->router->generate(
                            'payment_show',
                            [
                                'id' => $payment->getId(),
                            ]
                        )
                    );
                }
            }
        }
    }

    public function rollback(): void
    {
        $this->paymentQueue->clear();
    }

    private function isPossibleToSend(Payment $payment): bool
    {
        return $payment->getReceiptNumber()
            && $payment->getClient()
            && $payment->getClient()->getContacts()
            && $payment->isSendReceipt();
    }
}
