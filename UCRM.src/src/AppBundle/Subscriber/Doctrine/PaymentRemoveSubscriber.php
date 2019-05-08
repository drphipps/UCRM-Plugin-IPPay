<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Doctrine;

use AppBundle\Entity\Payment;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use EntitySubscribersBundle\Event\EntityEventSubscriber;

class PaymentRemoveSubscriber implements EntityEventSubscriber
{
    public function subscribesToEntity(LoadClassMetadataEventArgs $event): bool
    {
        return Payment::class === $event->getClassMetadata()->getName();
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::preRemove,
        ];
    }

    public function preRemove(Payment $payment, LifecycleEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getEntityManager();

        if ($payment->getProvider() && $payment->getPaymentDetailsId()) {
            $details = $entityManager->find($payment->getProvider()->getPaymentDetailsClass(), $payment->getPaymentDetailsId());

            if ($details) {
                $entityManager->remove($details);
            }
        }
    }
}
