<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\MercadoPago;

use AppBundle\Entity\Organization;

class ConnectionCheck
{
    /**
     * @var MercadoPagoFactory
     */
    private $mercadoPagoFactory;

    public function __construct(MercadoPagoFactory $mercadoPagoFactory)
    {
        $this->mercadoPagoFactory = $mercadoPagoFactory;
    }

    public function check(Organization $organization): void
    {
        try {
            $mp = $this->mercadoPagoFactory->create($organization);
            $mp->get_access_token();
        } catch (\Exception $exception) {
            if ($exception instanceof \MercadoPagoException) {
                throw $exception;
            }

            throw new \MercadoPagoException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
