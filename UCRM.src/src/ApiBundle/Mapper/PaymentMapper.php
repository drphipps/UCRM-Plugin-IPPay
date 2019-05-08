<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\PaymentCoverMap;
use ApiBundle\Map\PaymentMap;
use AppBundle\Entity\Client;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentCustom;
use AppBundle\Entity\User;

class PaymentMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return PaymentMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Payment::class;
    }

    /**
     * @param Payment $entity
     */
    protected function doMap(AbstractMap $map, $entity, PaymentCustom $customEntity = null): void
    {
        if (! $map instanceof PaymentMap) {
            throw new UnexpectedTypeException($map, PaymentMap::class);
        }

        $this->mapField($entity, $map, 'client', 'clientId', Client::class);
        $this->mapField($entity, $map, 'method');
        $this->mapField($entity, $map, 'checkNumber');
        $this->mapField($entity, $map, 'createdDate');
        $this->mapField($entity, $map, 'amount');
        $this->mapField($entity, $map, 'note');

        if ($map->currencyCode) {
            $currency = $this->entityManager->getRepository(Currency::class)
                ->findOneBy(
                    [
                        'code' => $map->currencyCode,
                    ]
                );

            if ($currency) {
                $entity->setCurrency($currency);
            } else {
                $this->errorCollector->add(
                    'currencyCode',
                    'This value is not valid.'
                );
            }
        } elseif ($entity->getClient() && $entity->getClient()->getOrganization()) {
            $entity->setCurrency($entity->getClient()->getOrganization()->getCurrency());
        }

        if ($map->applyToInvoicesAutomatically && ($map->invoiceIds || $map->invoiceId)) {
            $this->errorCollector->add(
                'applyToInvoicesAutomatically',
                'If this is true, invoiceIds and invoiceId must be empty.'
            );
        }

        if ($customEntity) {
            $this->mapField($customEntity, $map, 'providerName');
            $this->mapField($customEntity, $map, 'providerPaymentId');
            $this->mapField($customEntity, $map, 'providerPaymentTime');
            $this->mapField($customEntity, $map, 'amount');
        }

        $this->mapField($entity, $map, 'user', 'userId', User::class);
    }

    /**
     * @param Payment $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var PaymentMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'clientId', $entity->getClient(), 'id');
        $this->reflectField($map, 'method', $entity->getMethod());
        $this->reflectField($map, 'checkNumber', $entity->getCheckNumber());
        $this->reflectField($map, 'createdDate', $entity->getCreatedDate());
        $this->reflectField($map, 'amount', $entity->getAmount());
        $this->reflectField($map, 'currencyCode', $entity->getCurrency()->getCode());
        $this->reflectField($map, 'note', $entity->getNote());
        $this->reflectField($map, 'receiptSentDate', $entity->getReceiptSentDate());
        $this->reflectField($map, 'creditAmount', $entity->getCredit() ? $entity->getCredit()->getAmount() : 0.0);

        if (
            $entity->getProvider()
            && $entity->getProvider()->getPaymentDetailsClass() === PaymentCustom::class
            && $entity->getPaymentDetailsId()
        ) {
            /** @var PaymentCustom|null $customEntity */
            $customEntity = $this->entityManager->find(
                $entity->getProvider()->getPaymentDetailsClass(),
                $entity->getPaymentDetailsId()
            );

            if ($customEntity) {
                $this->reflectField($map, 'providerName', $customEntity->getProviderName());
                $this->reflectField($map, 'providerPaymentId', $customEntity->getProviderPaymentId());
                $this->reflectField($map, 'providerPaymentTime', $customEntity->getProviderPaymentTime());
            }
        }

        foreach ($entity->getPaymentCovers() as $paymentCover) {
            $paymentCoverMap = new PaymentCoverMap();
            $this->reflectField($paymentCoverMap, 'id', $paymentCover->getId());
            $this->reflectField($paymentCoverMap, 'invoiceId', $paymentCover->getInvoice(), 'id');
            $this->reflectField($paymentCoverMap, 'paymentId', $paymentCover->getPayment(), 'id');
            $this->reflectField($paymentCoverMap, 'refundId', $paymentCover->getRefund(), 'id');
            $this->reflectField($paymentCoverMap, 'amount', $paymentCover->getAmount());

            $map->paymentCovers[] = $paymentCoverMap;
        }

        $this->reflectField($map, 'userId', $entity->getUser(), 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'client' => 'clientId',
        ];
    }
}
