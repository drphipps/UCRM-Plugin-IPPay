<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\ClientZone;

use AppBundle\Component\Grid\Grid;
use AppBundle\Entity\Client;
use AppBundle\Entity\Payment;
use AppBundle\Grid\Payment\BasePaymentGridFactory;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;

class PaymentGridFactory extends BasePaymentGridFactory
{
    public function create(Client $client): Grid
    {
        $qb = $this->paymentDataProvider->getGridModel(false);
        $qb
            ->andWhere('p.client = :clientId')
            ->setParameter('clientId', $client->getId());

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('p_id', 'p.id');
        $grid->setRowUrl('client_zone_payment_show');
        $grid->setDefaultSort('p_created_date', Grid::DESC);

        $grid->attached();

        $grid
            ->addCustomColumn(
                'p_method',
                'Method',
                function ($row) {
                    /** @var Payment $payment */
                    $payment = $row[0];

                    return $this->gridHelper->trans($payment->getMethodName());
                }
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'p_amount',
                'Amount',
                function ($row) {
                    /** @var Payment $payment */
                    $payment = $row[0];

                    return $this->formatter->formatCurrency(
                        $payment->getAmount(),
                        $payment->getCurrency() ? $payment->getCurrency()->getCode() : null,
                        $payment->getClient()->getOrganization()->getLocale()
                    );
                }
            )
            ->setSortable()
            ->setAlignRight();

        $grid
            ->addTwigFilterColumn(
                'p_created_date',
                'p.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable()
            ->setOrderByCallback(
                function (QueryBuilder $model, string $direction) {
                    $model->orderBy('p.createdDate', $direction);
                    $model->addOrderBy('p.id', $direction);
                }
            );

        return $grid;
    }
}
