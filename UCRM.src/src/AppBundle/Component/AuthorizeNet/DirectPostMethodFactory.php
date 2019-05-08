<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\AuthorizeNet;

use AppBundle\Entity\Organization;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Service\PublicUrlGenerator;

class DirectPostMethodFactory
{
    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        PaymentFacade $paymentFacade,
        PublicUrlGenerator $publicUrlGenerator,
        \Twig_Environment $twig
    ) {
        $this->paymentFacade = $paymentFacade;
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->twig = $twig;
    }

    public function create(Organization $organization, bool $sandbox, string $token): DirectPostMethod
    {
        $dpm = new DirectPostMethod(
            $this->paymentFacade,
            $this->publicUrlGenerator,
            $this->twig
        );

        return $dpm
            ->setOrganization($organization)
            ->setSandbox($sandbox)
            ->setToken($token);
    }
}
