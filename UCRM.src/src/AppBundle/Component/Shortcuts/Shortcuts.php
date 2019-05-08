<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Shortcuts;

use AppBundle\DataProvider\ShortcutDataProvider;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Shortcuts
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var ShortcutParameters
     */
    private $shortcutParameters;

    /**
     * @var ShortcutDataProvider
     */
    private $shortcutDataProvider;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        \Twig_Environment $twig,
        ShortcutParameters $shortcutParameters,
        ShortcutDataProvider $shortcutDataProvider,
        TokenStorageInterface $tokenStorage
    ) {
        $this->twig = $twig;
        $this->shortcutParameters = $shortcutParameters;
        $this->shortcutDataProvider = $shortcutDataProvider;
        $this->tokenStorage = $tokenStorage;
    }

    public function render(): void
    {
        $token = $this->tokenStorage->getToken();
        if (! $token) {
            throw new \RuntimeException('Can\'t get token.');
        }
        $user = $token->getUser();
        if (! $user instanceof User) {
            throw new \RuntimeException('Can\'t User entity from token.');
        }

        echo $this->twig->render(
            'shortcuts/shortcuts.html.twig',
            [
                'shortcutParameters' => $this->shortcutParameters->get(),
                'shortcuts' => $this->shortcutDataProvider->get($user),
            ]
        );
    }
}
