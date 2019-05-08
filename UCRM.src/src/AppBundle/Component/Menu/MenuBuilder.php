<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

use AppBundle\Security\Permission;
use AppBundle\Security\PermissionGrantedChecker;

final class MenuBuilder
{
    /**
     * @var PermissionGrantedChecker|null
     */
    private $permissionGrantedChecker;

    /**
     * @var MenuItem[]
     */
    private $items = [];

    /**
     * @var ParentMenuItem[]
     */
    private $parents = [];

    /**
     * @return MenuItem[]
     */
    public function build(): array
    {
        return array_filter(
            $this->items,
            function (MenuItem $item) {
                return ! $item instanceof ParentMenuItem || $item->getChildren();
            }
        );
    }

    public function enablePermissionCheck(PermissionGrantedChecker $permissionGrantedChecker): void
    {
        $this->permissionGrantedChecker = $permissionGrantedChecker;
    }

    public function addLink(
        string $name,
        array $targets,
        array $activeControllers,
        ?string $icon = null
    ): void {
        $route = $this->findRoute($targets);

        if ($route === null) {
            return;
        }

        $this->items[] = new RouteMenuItem($name, $route, $icon, $activeControllers);
    }

    public function addParent(
        string $name,
        ?string $icon = null
    ): ParentMenuItem {
        $item = new ParentMenuItem($name, $icon);

        $this->items[] = $item;
        $this->parents[$name] = $item;

        return $item;
    }

    public function addChildLink(
        ParentMenuItem $parent,
        string $name,
        array $targets,
        array $activeControllers
    ): void {
        $route = $this->findRoute($targets);

        if ($route === null) {
            return;
        }

        $child = new RouteMenuItem($name, $route, null, $activeControllers);

        $parent->addChild($child);
    }

    /**
     * The $targets parameter is an array of possible target locations.
     * The first target the user is permitted to access will be used as the link.
     * The array should have route as index and permission as value.
     * Permission is either string (controller class) or ['controller' => ..., 'permission' => ...] array.
     */
    private function findRoute(array $targets): ?string
    {
        foreach ($targets as $route => $controller) {
            if ($this->canDisplayMenuItem($controller)) {
                return $route;
            }
        }

        return null;
    }

    private function canDisplayMenuItem($item): bool
    {
        if (! $this->permissionGrantedChecker) {
            return true;
        }

        $controller = is_array($item) ? $item['controller'] : $item;
        $permission = is_array($item) ? $item['permission'] : Permission::VIEW;

        return $this->permissionGrantedChecker->isGranted($permission, $controller);
    }

    public function addPluginLink(PluginMenuItemData $item): void
    {
        if ($item->key) {
            $pluginLinkMenuItem = new PluginLinkMenuItem($item);

            if (array_key_exists($item->key, $this->parents)) {
                $parent = $this->parents[$item->key];
            } else {
                $parent = $this->addParent(
                    $item->key,
                    'ubnt-icon--box'
                );
                $parent->disableTranslations();
            }

            $parent->addChild($pluginLinkMenuItem);
        } else {
            $pluginLinkMenuItem = new PluginLinkMenuItem($item, 'ubnt-icon--box');

            $this->items[] = $pluginLinkMenuItem;
        }
    }
}
