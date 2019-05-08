<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class RouteMenuItem implements MenuItem
{
    use MenuItemTrait;

    /**
     * @var string
     */
    private $route;

    /**
     * @var array
     */
    private $activeControllers = [];

    /**
     * @var array
     */
    private $activeRoutes = [];

    public function __construct(
        string $name,
        string $route = null,
        ?string $icon = null,
        array $activeControllers = []
    ) {
        $this->name = $name;
        $this->route = $route;
        $this->icon = $icon;
        $this->activeControllers = $activeControllers;
    }

    public function getUrl(RouterInterface $router): string
    {
        return $router->generate($this->route);
    }

    public function checkActive(Request $request, RouterInterface $router): void
    {
        $this->active = $this->getUrl($router) == $request->getUri()
            || in_array($request->get('_route'), $this->activeRoutes, true)
            || $this->isSameController($this->activeControllers, $request->get('_controller'));
    }

    public function useTranslations(): bool
    {
        return true;
    }

    public function openInNewWindow(): bool
    {
        return false;
    }

    private function isSameController(array $activeControllers, string $currentController): bool
    {
        $currentController = Strings::replace($currentController, '/::.+$/', '');

        return in_array($currentController, $activeControllers, true);
    }
}
