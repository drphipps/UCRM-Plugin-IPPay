<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Grid;

use AppBundle\Entity\General;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Service\Options;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;

class GridHelper
{
    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        PermissionGrantedChecker $permissionGrantedChecker,
        Session $session,
        TranslatorInterface $translator,
        Options $options,
        TokenStorageInterface $tokenStorage,
        RouterInterface $router
    ) {
        $this->permissionGrantedChecker = $permissionGrantedChecker;
        $this->session = $session;
        $this->translator = $translator;
        $this->options = $options;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    /**
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessPermissionGranted(string $permissionLevel, string $permissionName)
    {
        $this->permissionGrantedChecker->denyAccessUnlessGranted($permissionLevel, $permissionName);
    }

    public function isSpecialPermissionGranted(string $permissionName): bool
    {
        return $this->permissionGrantedChecker->isGrantedSpecial($permissionName);
    }

    public function addTranslatedFlash(
        string $type,
        string $message,
        ?int $number = null,
        array $messageParameters = []
    ) {
        if (! in_array($type, ['success', 'info', 'warning', 'error'], true)) {
            @trigger_error(
                'Supported flash message types are: \'success\', \'info\', \'warning\' and \'error\'.',
                E_USER_DEPRECATED
            );
        }

        $message = null === $number
            ? $this->trans($message, $messageParameters)
            : $message = $this->transChoice($message, $number, $messageParameters);

        $this->session->getFlashBag()->add($type, $message);
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function transChoice(
        string $id,
        int $number,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * Gets application option stored in database.
     */
    public function getOption(string $code, $default = null)
    {
        return $this->options->get($code, $default);
    }

    public function isSandbox(): bool
    {
        return (bool) $this->options->getGeneral(General::SANDBOX_MODE);
    }

    public function getUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    public function generateUrl(string $route, array $parameters = []): string
    {
        return $this->router->generate($route, $parameters);
    }
}
