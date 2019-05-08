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
use ApiBundle\Map\RefundMap;
use AppBundle\Entity\Client;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Refund;

class RefundMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return RefundMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Refund::class;
    }

    /**
     * @param Refund $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof RefundMap) {
            throw new UnexpectedTypeException($map, RefundMap::class);
        }

        $this->mapField($entity, $map, 'method');
        $this->mapField($entity, $map, 'createdDate');
        $this->mapField($entity, $map, 'amount');
        $this->mapField($entity, $map, 'note');
        $this->mapField($entity, $map, 'client', 'clientId', Client::class);

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
    }

    /**
     * @param Refund $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var RefundMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'method', $entity->getMethod());
        $this->reflectField($map, 'createdDate', $entity->getCreatedDate());
        $this->reflectField($map, 'amount', $entity->getAmount());
        $this->reflectField($map, 'note', $entity->getNote());
        $this->reflectField($map, 'clientId', $entity->getClient(), 'id');
        $this->reflectField($map, 'currencyCode', $entity->getCurrency()->getCode());

        foreach ($entity->getPaymentCovers() as $paymentCover) {
            $paymentCoverMap = new PaymentCoverMap();
            $this->reflectField($paymentCoverMap, 'id', $paymentCover->getId());
            $this->reflectField($paymentCoverMap, 'invoiceId', $paymentCover->getInvoice(), 'id');
            $this->reflectField($paymentCoverMap, 'paymentId', $paymentCover->getPayment(), 'id');
            $this->reflectField($paymentCoverMap, 'refundId', $paymentCover->getRefund(), 'id');
            $this->reflectField($paymentCoverMap, 'amount', $paymentCover->getAmount());

            $map->paymentCovers[] = $paymentCoverMap;
        }
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
