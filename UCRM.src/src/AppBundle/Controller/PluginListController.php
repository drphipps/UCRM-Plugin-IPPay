<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\PluginListDataProvider;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/plugins")
 * @PermissionControllerName(PluginController::class)
 */
class PluginListController extends BaseController
{
    /**
     * @var PluginListDataProvider
     */
    private $pluginsDataProvider;

    public function __construct(
        PluginListDataProvider $pluginsDataProvider
    ) {
        $this->pluginsDataProvider = $pluginsDataProvider;
    }

    /**
     * @Route("", name="plugin_index")
     * @Method({"GET"})
     * @Permission("view")
     * @Searchable(heading="Plugins", path="System -> Plugins")
     */
    public function indexAction(): Response
    {
        $installedPlugins = $this->get(PluginListDataProvider::class)->getInstalledPlugins();

        return $this->render(
            'plugins/index.html.twig',
            [
                'installedPlugins' => $installedPlugins,
            ]
        );
    }

    /**
     * @Route("/available-plugins", name="plugin_available_plugins", options={"expose"=true})
     * @Method({"GET"})
     * @Permission("view")
     */
    public function loadAvailablePlugins(): Response
    {
        [$installedPlugins, $availablePlugins] = $this->pluginsDataProvider->getAllPlugins();

        $this->invalidateTemplate(
            'available-plugin-list',
            'plugins/components/plugin_list.html.twig',
            [
                'plugins' => $availablePlugins,
            ]
        );

        $this->invalidateTemplate(
            'installed-plugin-list',
            'plugins/components/plugin_list.html.twig',
            [
                'plugins' => $installedPlugins,
            ]
        );

        return $this->createAjaxResponse();
    }
}
