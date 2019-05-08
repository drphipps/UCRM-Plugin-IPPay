<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\ClientZone;

use AppBundle\DataProvider\ClientZonePageDataProvider;

class PageMenu
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var ClientZonePageDataProvider
     */
    private $clientZonePageDataProvider;

    public function __construct(\Twig_Environment $twig, ClientZonePageDataProvider $clientZonePageDataProvider)
    {
        $this->twig = $twig;
        $this->clientZonePageDataProvider = $clientZonePageDataProvider;
    }

    /**
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function render(): void
    {
        echo $this->twig->render(
            'client_zone/page/menu.html.twig',
            [
                'pages' => $this->clientZonePageDataProvider->getPublic(),
            ]
        );
    }
}
