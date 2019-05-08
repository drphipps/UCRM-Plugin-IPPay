<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

use AppBundle\Entity\User;
use AppBundle\Security\PermissionGrantedChecker;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig_Environment;

final class Menu
{
    /**
     * @var array
     */
    private $items = [];

    /**
     * @var bool
     */
    private $isAdmin;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var PluginsMenuItemsLoader
     */
    private $pluginsMenuItemsLoader;

    public function __construct(
        Twig_Environment $twig,
        RouterInterface $router,
        PermissionGrantedChecker $permissionGrantedChecker,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        PluginsMenuItemsLoader $pluginsMenuItemsLoader
    ) {
        $this->twig = $twig;
        $this->router = $router;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
        $this->requestStack = $requestStack;
        $this->pluginsMenuItemsLoader = $pluginsMenuItemsLoader;

        $token = $tokenStorage->getToken();
        if (! $token || ! $token->getUser() instanceof User) {
            throw new \RuntimeException('No User found.');
        }

        $this->isAdmin = $token->getUser()->isAdmin();
    }

    public function buildView(): string
    {
        if ($this->isAdmin) {
            $this->assembleAdminMenu();
        } else {
            $this->assembleClientMenu();
        }

        foreach ($this->items as $item) {
            $item->checkActive($this->requestStack->getCurrentRequest(), $this->router);
        }

        return $this->twig->render(
            'components/main_menu.html.twig',
            [
                'items' => $this->items,
                'menu' => $this,
                'isAdmin' => $this->isAdmin,
            ]
        );
    }

    private function assembleAdminMenu(): void
    {
        $builder = (new AdminMenu($this->permissionGrantedChecker))->assemble();

        $this->addPluginLinks($builder, 'admin');

        $this->items = $builder->build();
    }

    private function assembleClientMenu(): void
    {
        $builder = (new ClientMenu())->assemble();

        $this->addPluginLinks($builder, 'client');

        $this->items = $builder->build();
    }

    private function addPluginLinks(MenuBuilder $builder, string $linkType): void
    {
        $plugins = $this->pluginsMenuItemsLoader->load();

        /** @var PluginMenuItemData $item */
        foreach ($plugins as $item) {
            if ($item->type !== $linkType) {
                continue;
            }

            $builder->addPluginLink($item);
        }
    }

    /**
     * Public because it's used in Twig.
     */
    public function getMenuItemUrl(MenuItem $item): string
    {
        return $item->getUrl($this->router);
    }
}
