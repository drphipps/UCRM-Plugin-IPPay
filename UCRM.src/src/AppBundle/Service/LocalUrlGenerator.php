<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\DataProvider\CertificateDataProvider;
use Symfony\Component\Routing\RouterInterface;

class LocalUrlGenerator
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CertificateDataProvider
     */
    private $certificateDataProvider;

    public function __construct(
        RouterInterface $router,
        CertificateDataProvider $certificateDataProvider
    ) {
        $this->router = $router;
        $this->certificateDataProvider = $certificateDataProvider;
    }

    /**
     * Generates an internally accessible URL from the given parameters.
     */
    public function generate(string $route, array $parameters = []): string
    {
        $useHttps = (
            $this->certificateDataProvider->isCustomEnabled()
            || $this->certificateDataProvider->isLetsEncryptEnabled()
        );

        return ($useHttps ? 'https' : 'http')
            . '://localhost/'
            . ltrim($this->router->generate($route, $parameters), '/');
    }
}
