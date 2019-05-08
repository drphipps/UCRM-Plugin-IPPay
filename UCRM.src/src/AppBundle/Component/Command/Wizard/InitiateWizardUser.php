<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Wizard;

use AppBundle\Entity\User;
use AppBundle\Facade\UserFacade;
use Doctrine\ORM\EntityManager;

class InitiateWizardUser
{
    private const UCRM_USERNAME = 'UCRM_USERNAME';
    private const UCRM_PASSWORD = 'UCRM_PASSWORD';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserFacade
     */
    private $userFacade;

    public function __construct(EntityManager $em, UserFacade $userFacade)
    {
        $this->em = $em;
        $this->userFacade = $userFacade;
    }

    public function init(): void
    {
        $wizard = $this->em->getRepository(User::class)->getWizardUser();
        $username = getenv(self::UCRM_USERNAME);
        $password = getenv(self::UCRM_PASSWORD);

        if (
            ! $wizard
            || ! $username
            || ! $password
            || $username === 'null'
            || $password === 'null'
        ) {
            return;
        }

        $wizard->setUsername($username);
        $wizard->setPlainPassword($password);
        $this->userFacade->handleUpdate($wizard);
    }
}
