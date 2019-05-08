<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

use AppBundle\Controller\ClientZone;

final class ClientMenu
{
    /**
     * Builds application menu for client.
     */
    public function assemble(): MenuBuilder
    {
        $builder = new MenuBuilder();

        $builder->addLink(
            'Account',
            [
                'client_zone_client_index' => ClientZone\ClientController::class,
            ],
            [
                ClientZone\ClientController::class,
            ],
            'ubnt-icon--profile'
        );

        $builder->addLink(
            'Payments',
            [
                'client_zone_payment_index' => ClientZone\PaymentController::class,
            ],
            [
                ClientZone\PaymentController::class,
            ],
            'ubnt-icon--creditcard'
        );

        $builder->addLink(
            'Invoices',
            [
                'client_zone_invoice_index' => ClientZone\InvoiceController::class,
            ],
            [
                ClientZone\InvoiceController::class,
            ],
            'ubnt-icon--box-archive'
        );

        $builder->addLink(
            'Quotes',
            [
                'client_zone_quote_index' => ClientZone\QuoteController::class,
            ],
            [
                ClientZone\QuoteController::class,
            ],
            'ucrm-icon--billing'
        );

        $builder->addLink(
            'Support',
            [
                'client_zone_support_index' => ClientZone\SupportController::class,
            ],
            [
                ClientZone\SupportController::class,
            ],
            'ubnt-icon--question'
        );

        return $builder;
    }
}
