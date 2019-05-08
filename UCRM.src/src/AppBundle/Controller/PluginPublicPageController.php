<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\PluginDataProvider;
use AppBundle\Entity\Plugin;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/plugins")
 * @PermissionControllerName(PluginController::class)
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
     * @Route("/{id}/public", name="plugin_public", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("view")
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
