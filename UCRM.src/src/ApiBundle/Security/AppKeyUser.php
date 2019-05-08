<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Security;

use AppBundle\Entity\AppKey;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class AppKeyUser implements UserInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var AppKey
     */
    private $appKey;

    public function __construct(AppKey $appKey)
    {
        $this->name = $appKey->getName();
        $this->type = $appKey->getType();
        $this->appKey = $appKey;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAppKey(): AppKey
    {
        return $this->appKey;
    }

    public function getRoles(): array
    {
        return [
            User::ROLE_ADMIN,
        ];
    }

    public function getPassword(): string
    {
        return '';
    }

    public function getSalt(): string
    {
        return '';
    }

    public function getUsername(): string
    {
        return $this->name . ' (App key)';
    }

    public function eraseCredentials(): void
    {
    }
}
