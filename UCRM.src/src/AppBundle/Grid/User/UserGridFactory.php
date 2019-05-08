<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\User;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\UserController;
use AppBundle\DataProvider\UserDataProvider;
use AppBundle\Entity\User;
use AppBundle\Facade\UserFacade;
use Nette\Utils\Html;

class UserGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var UserFacade
     */
    private $userFacade;

    /**
     * @var GridHelper
     */
    private $gridHelper;

    /**
     * @var UserDataProvider
     */
    private $userDataProvider;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        GridFactory $gridFactory,
        UserFacade $userFacade,
        GridHelper $gridHelper,
        UserDataProvider $userDataProvider,
        \Twig_Environment $twig
    ) {
        $this->gridFactory = $gridFactory;
        $this->userFacade = $userFacade;
        $this->gridHelper = $gridHelper;
        $this->userDataProvider = $userDataProvider;
        $this->twig = $twig;
    }

    public function create(): Grid
    {
        $qb = $this->userDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('u_id', 'u.id');
        $grid->addIdentifier('u_email', 'u.email');
        $grid->setDefaultSort('u.fullName');
        $grid->setRowUrl('user_show');

        $grid->attached();

        $grid
            ->addRawCustomColumn(
                'u.fullName',
                'User',
                function ($row) {
                    return $this->renderUserNameWithBadges($row[0]);
                }
            )
            ->setSortable();
        $grid
            ->addRawCustomColumn(
                'u_email',
                'Email',
                function ($row) {
                    if (filter_var($row['u_email'], FILTER_VALIDATE_EMAIL)) {
                        return Html::el('a')
                            ->setAttribute(
                                'href',
                                sprintf('mailto:%s', htmlspecialchars($row['u_email'] ?? '', ENT_QUOTES))
                            )
                            ->setText($row['u_email']);
                    }

                    return htmlspecialchars($row['u_email'] ?? '', ENT_QUOTES);
                }
            )
            ->setSortable();
        $grid->addTextColumn('g_name', 'g.name', 'Group')->setSortable();
        $grid->addTwigFilterColumn('u_isActive', 'u.isActive', 'Is Active', 'yesNo')
            ->setSortable();

        $grid->addCustomColumn(
            'u_2fa',
            '2FA enabled',
            function ($row) {
                /** @var User $user */
                $user = $row[0];

                return $user->isGoogleAuthenticatorEnabled()
                    ? $this->gridHelper->trans('Yes')
                    : $this->gridHelper->trans('No');
            }
        );

        $grid->addEditActionButton('user_edit', [], UserController::class);

        $grid
            ->addDeleteActionButton('user_delete', [], UserController::class)
            ->addRenderCondition(
                function ($row) {
                    /** @var User $user */
                    $user = $row[0];

                    return $user->getRole() !== User::ROLE_SUPER_ADMIN;
                }
            );

        return $grid;
    }

    private function renderUserNameWithBadges(User $user): string
    {
        return $this->twig->render(
            'user/components/grid_user_name_with_badges.html.twig',
            [
                'user' => $user,
            ]
        );
    }
}
