<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

use AppBundle\Controller\ClientZone\PluginPublicPageController as ClientZonePluginPublicPageController;
use AppBundle\Controller\PluginPublicPageController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class PluginLinkMenuItem implements MenuItem
{
    use MenuItemTrait;

    /**
     * @var PluginMenuItemData
     */
    private $item;

    public function __construct(
        PluginMenuItemData $item,
        ?string $icon = null
    ) {
        $this->item = $item;
        $this->icon = $icon;
    }

    public function getName(): string
    {
        return $this->item->label;
    }

    public function getUrl(RouterInterface $router): string
    {
        if ($this->item->target === 'iframe') {
            return $router->generate(
                $this->item->type === 'client' ? 'client_zone_plugin_public' : 'plugin_public',
                [
                    'id' => $this->item->pluginId,
                    'parameters' => $this->item->parameters,
                ]
            );
        }

        return $this->item->link;
    }

    public function checkActive(Request $request, RouterInterface $router): void
    {
        $controller = $this->item->type === 'client'
            ? ClientZonePluginPublicPageController::class
            : PluginPublicPageController::class;

        $this->active = $request->get('_controller') === $controller . '::showPublicPageAction'
            && (int) $request->get('id') === $this->item->pluginId
            && (array) $request->query->get('parameters') === $this->item->parameters;
    }

    public function useTranslations(): bool
    {
        return false;
    }

    public function openInNewWindow(): bool
    {
        return $this->item->target !== 'iframe';
    }
}
