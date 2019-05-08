<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller\ClientZone;

use AppBundle\DataProvider\PluginDataProvider;
use AppBundle\Entity\Plugin;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client-zone/plugins")
 */
class PluginPublicPageController extends BaseController
{
    /**
     * @var PluginDataProvider
     */
    private $pluginDataProvider;

    public function __construct(PluginDataProvider $pluginDataProvider)
    {
        $this->pluginDataProvider = $pluginDataProvider;
    }

    /**
     * @Route("/{id}", name="client_zone_plugin_public", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("guest")
     */
    public function showPublicPageAction(Request $request, Plugin $plugin): Response
    {
        $parameters = $request->query->get('parameters');

        $link = $this->pluginDataProvider->getUrl($plugin, (array) $parameters);

        if (! $link) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'plugins/public.html.twig',
            [
                'plugin' => $plugin,
                'iframeLink' => $link,
            ]
        );
    }
}
