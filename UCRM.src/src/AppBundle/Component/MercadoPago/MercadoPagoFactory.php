<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\MercadoPago;

use AppBundle\Entity\Organization;
use AppBundle\Service\Options;

/**
 * Handles creating and seeding of MercadoPago SDK.
 * Sandbox is commented out, because as of 11/8/2017 it's in maintenance and does not work properly.
 */
class MercadoPagoFactory
{
    /**
     * @var Options
     */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function create(Organization $organization): \MP
    {
        $mp = new \MP($organization->getMercadoPagoClientId(), $organization->getMercadoPagoClientSecret());
        // This is commented out on purpose, because MercadoPago sandbox mode is not sending notifications properly.
        // Use test users instead - https://www.mercadopago.com.mx/developers/en/solutions/payments/basic-checkout/test/test-users/
        // $mp->sandbox_mode((bool) $this->options->getGeneral(General::SANDBOX_MODE));

        return $mp;
    }
}
