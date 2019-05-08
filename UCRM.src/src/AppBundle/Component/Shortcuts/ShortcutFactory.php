<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Shortcuts;

use AppBundle\Entity\Shortcut;
use AppBundle\Entity\User;

class ShortcutFactory
{
    public function create(User $user, string $route, array $parameters, ?string $suffix): Shortcut
    {
        $shortcut = new Shortcut();
        $shortcut->setUser($user);
        $shortcut->setRoute($route);
        $shortcut->setParameters($parameters);
        $shortcut->setSuffix($suffix);

        return $shortcut;
    }
}
