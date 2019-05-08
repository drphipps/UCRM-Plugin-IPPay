<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class ParentMenuItem implements MenuItem
{
    use MenuItemTrait;

    /**
     * @var MenuItem[]
     */
    private $children = [];

    /**
     * @var bool
     */
    private $useTranslations = true;

    public function __construct(
        string $name,
        ?string $icon = null
    ) {
        $this->name = $name;
        $this->icon = $icon;
    }

    public function getUrl(RouterInterface $router): string
    {
        return '#';
    }

    public function checkActive(Request $request, RouterInterface $router): void
    {
        foreach ($this->children as $item) {
            $item->checkActive($request, $router);
            $this->active = $this->active || $item->isActive();
        }
    }

    public function useTranslations(): bool
    {
        return $this->useTranslations;
    }

    public function openInNewWindow(): bool
    {
        return false;
    }

    public function addChild(MenuItem $childItem): void
    {
        $this->children[] = $childItem;
    }

    /**
     * @return MenuItem[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function disableTranslations(): void
    {
        $this->useTranslations = false;
    }
}
