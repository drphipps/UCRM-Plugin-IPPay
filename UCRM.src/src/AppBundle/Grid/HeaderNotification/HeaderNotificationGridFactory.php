<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\HeaderNotification;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\User;
use AppBundle\Facade\HeaderNotificationFacade;
use AppBundle\Util\Formatter;

class HeaderNotificationGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var HeaderNotificationFacade
     */
    private $headerNotificationFacade;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        GridFactory $gridFactory,
        HeaderNotificationFacade $headerNotificationFacade,
        \Twig_Environment $twig
    ) {
        $this->gridFactory = $gridFactory;
        $this->headerNotificationFacade = $headerNotificationFacade;
        $this->twig = $twig;
    }

    public function create(User $user): Grid
    {
        $qb = $this->headerNotificationFacade->getGridModel($user);
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('hn_id', 'hn.id');
        $grid->setDefaultSort(null);

        $grid->attached();

        $grid->addRawCustomColumn(
            'hn_title',
            'Subject',
            function ($row) {
                /** @var HeaderNotification $notification */
                $notification = $row[0]->getHeaderNotification();

                return $this->renderStatusBall($notification->getType(), $notification->getTitle());
            }
        );

        $grid->addTextColumn('hn_description', 'hn.description', 'Message');
        $grid->addTwigFilterColumn(
            'hn_created_date',
            'hn.createdDate',
            'Date',
            'localizedDate',
            [Formatter::DEFAULT, Formatter::MEDIUM]
        );

        return $grid;
    }

    private function renderStatusBall(int $status, string $label): string
    {
        $type = '';

        switch ($status) {
            case HeaderNotification::TYPE_INFO:
                $type = 'primary';
                break;
            case HeaderNotification::TYPE_SUCCESS:
                $type = 'success';
                break;
            case HeaderNotification::TYPE_DANGER:
                $type = 'danger';
                break;
            case HeaderNotification::TYPE_WARNING:
                $type = 'warning';
                break;
        }

        return $this->twig->render(
            'client/components/view/status_ball.html.twig',
            [
                'type' => $type,
                'label' => $label,
            ]
        );
    }
}
