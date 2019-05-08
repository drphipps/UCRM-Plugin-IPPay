<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

interface MenuItem
{
    public function getName(): string;

    public function isActive(): bool;

    public function getIcon(): ?string;

    public function checkActive(Request $request, RouterInterface $router): void;

    public function getUrl(RouterInterface $router): string;

    public function useTranslations(): bool;

    public function openInNewWindow(): bool;
}
