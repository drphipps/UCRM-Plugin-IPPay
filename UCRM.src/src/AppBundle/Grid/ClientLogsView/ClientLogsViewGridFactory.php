<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\ClientLogsView;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\DataProvider\ClientLogsViewDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientLog;
use AppBundle\Entity\ClientLogsView;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Option;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Security\SpecialPermission;
use AppBundle\Service\EmailLog\EmailLogRenderer;
use AppBundle\Service\EntityLog\EntityLogRenderer;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Html;

class ClientLogsViewGridFactory
{
    /**
     * @var ClientLogsViewDataProvider
     */
    private $clientLogsViewDataProvider;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EmailLogRenderer
     */
    private $emailLogRenderer;

    /**
     * @var EntityLogRenderer
     */
    private $entityLogRenderer;

    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var GridHelper
     */
    private $gridHelper;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        ClientLogsViewDataProvider $clientLogsViewDataProvider,
        EntityManagerInterface $entityManager,
        EmailLogRenderer $emailLogRenderer,
        EntityLogRenderer $entityLogRenderer,
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        PermissionGrantedChecker $permissionGrantedChecker,
        \Twig_Environment $twig
    ) {
        $this->clientLogsViewDataProvider = $clientLogsViewDataProvider;
        $this->entityManager = $entityManager;
        $this->emailLogRenderer = $emailLogRenderer;
        $this->entityLogRenderer = $entityLogRenderer;
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
        $this->twig = $twig;
    }

    public function create(Client $client, array $filters = []): Grid
    {
        $qb = $this->clientLogsViewDataProvider->getGridModel($client, $filters);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);

        $grid->addIdentifier('el_id', 'el.id');
        $grid->setRouterUrlSuffix('#logs');
        $grid->setPostFetchCallback($this->clientLogsViewDataProvider->getGridPostFetchCallback());
        $grid->setType(Grid::GRID_TYPE_SMALL);
        $grid->setRowUrl(
            function ($row) {
                /** @var ClientLogsView $entity */
                $entity = $row[0];
                if ($entity->getLogType() === ClientLogsView::LOG_TYPE_ENTITY_LOG) {
                    return 'system_log_detail';
                }
                if ($entity->getLogType() === ClientLogsView::LOG_TYPE_EMAIL_LOG) {
                    return 'email_log_show';
                }

                return null;
            },
            null,
            [
                'id' => 'el_log_id',
            ]
        );
        $grid->setRowUrlIsModal();
        $grid->setShowHeader(false);
        $grid->setShowFooter(false);
        $grid->setRoute('client_show');
        $grid->addRouterUrlParam('id', $client->getId());

        foreach ($filters as $key => $value) {
            $grid->addRouterUrlParam($key, $value, true);
        }

        $grid->attached();

        $grid->addRawCustomColumn(
            'el_type',
            'Type',
            function ($row) {
                /** @var ClientLogsView $entity */
                $entity = $row[0];

                switch ($entity->getLogType()) {
                    case ClientLogsView::LOG_TYPE_ENTITY_LOG:
                        $type = 'system';

                        break;
                    case ClientLogsView::LOG_TYPE_CLIENT_LOG:
                        $type = 'client';

                        break;
                    case ClientLogsView::LOG_TYPE_EMAIL_LOG:
                        $type = 'email';

                        break;
                    default:
                        throw new \RuntimeException('Unknown ClientLogsView logType.');
                }

                return $this->twig->render(
                    'client/components/view/client_log_type.html.twig',
                    [
                        'type' => $type,
                        'createdDate' => $entity->getCreatedDate(),
                    ]
                );
            }
        )
            ->setCssClass('log__column__type');

        $grid->addRawCustomColumn(
            'el_user',
            'User',
            function ($row) {
                /** @var ClientLogsView $entity */
                $entity = $row[0];
                $title = null;

                switch ($entity->getLogType()) {
                    case ClientLogsView::LOG_TYPE_ENTITY_LOG:
                        $entityLog = $this->entityManager->getRepository(EntityLog::class)->find($entity->getLogId());
                        $message = $entityLog->getUser()
                            ? $entityLog->getUser()->getNameForView()
                            : $this->gridHelper->trans('System');

                        break;
                    case ClientLogsView::LOG_TYPE_EMAIL_LOG:
                        $message = BaseColumn::EMPTY_COLUMN;

                        break;
                    case ClientLogsView::LOG_TYPE_CLIENT_LOG:
                        $clientLog = $this->entityManager->getRepository(ClientLog::class)->find($entity->getLogId());
                        $message = $clientLog->getUser()
                            ? $clientLog->getUser()->getNameForView()
                            : BaseColumn::EMPTY_COLUMN;
                        $title = $message === BaseColumn::EMPTY_COLUMN ? null : $message;

                        break;
                    default:
                        throw new \RuntimeException('Unknown ClientLogsView logType.');
                }

                if ($message !== BaseColumn::EMPTY_COLUMN) {
                    $message = Html::el('strong')
                        ->setText($message);

                    if (null !== $title) {
                        $message->setAttribute('title', $title);
                    }
                }

                return (string) $message;
            }
        )
            ->setCssClass('log__column__user');

        $grid
            ->addRawCustomColumn(
                'el_log',
                'Message',
                function ($row) {
                    /** @var ClientLogsView $entity */
                    $entity = $row[0];

                    switch ($entity->getLogType()) {
                        case ClientLogsView::LOG_TYPE_ENTITY_LOG:
                            /**
                             * Entities are loaded in a bulk using the postFetchCallback. The find here will not trigger new queries.
                             *
                             * @see ClientLogsViewDataProvider::getGridPostFetchCallback()
                             */
                            $entityLog = $this->entityManager->getRepository(EntityLog::class)->find($entity->getLogId());
                            $message = htmlspecialchars(
                                $this->entityLogRenderer->renderMessage($entityLog),
                                ENT_QUOTES
                            );

                            break;
                        case ClientLogsView::LOG_TYPE_EMAIL_LOG:
                            /**
                             * Entities are loaded in a bulk using the postFetchCallback. The find here will not trigger new queries.
                             *
                             * @see ClientLogsViewDataProvider::getGridPostFetchCallback()
                             */
                            $emailLog = $this->entityManager->getRepository(EmailLog::class)->find($entity->getLogId());
                            $message = $this->emailLogRenderer->renderMessage($emailLog, true, true);

                            break;
                        case ClientLogsView::LOG_TYPE_CLIENT_LOG:
                            $message = nl2br(htmlspecialchars($entity->getMessage() ?? '', ENT_QUOTES));

                            break;
                        default:
                            throw new \RuntimeException('Unknown ClientLogsView logType.');
                    }

                    return $message;
                }
            )
            ->setCssClass('log__column__message');

        $grid
            ->addTwigFilterColumn(
                'el_created_date',
                'el.createdDate',
                'Date',
                'localizedDateToday',
                [Formatter::NONE, Formatter::MEDIUM, Formatter::DEFAULT, Formatter::MEDIUM]
            )
            ->setCssClass('log__column__date')
            ->setSortable();

        if ($this->gridHelper->getOption(Option::TICKETING_ENABLED)) {
            $newTicketButton = $grid->addActionButton(
                'client_log_new_ticket',
                ['id' => 'el_log_id']
            );
            $newTicketButton->addRenderCondition(
                function ($row) {
                    /** @var ClientLogsView $entity */
                    $entity = $row[0];

                    return $entity->getLogType() === ClientLogsView::LOG_TYPE_CLIENT_LOG;
                }
            );
            $newTicketButton->setIsModal(true);
            $newTicketButton->setIcon('ucrm-icon--message-circle-plus');
            $newTicketButton->setData(
                [
                    'tooltip' => $this->gridHelper->trans('Add ticket'),
                ]
            );
            $newTicketButton->setCssClasses(
                [
                    'action',
                ],
                true
            );
        }

        if ($this->permissionGrantedChecker->isGrantedSpecial(SpecialPermission::CLIENT_LOG_EDIT)) {
            $editButton = $grid->addEditActionButton(
                'client_log_edit',
                ['id' => 'el_log_id'] + $filters,
                null,
                true
            );
            $editButton->addRenderCondition(
                function ($row) {
                    /** @var ClientLogsView $entity */
                    $entity = $row[0];

                    return $entity->getLogType() === ClientLogsView::LOG_TYPE_CLIENT_LOG;
                }
            );
            $editButton->setCssClasses(
                [
                    'action',
                ],
                true
            );

            $deleteButton = $grid->addDeleteActionButton(
                'client_log_delete',
                [
                    'id' => 'el_log_id',
                ]
            );
            $deleteButton->addRenderCondition(
                function ($row) {
                    /** @var ClientLogsView $entity */
                    $entity = $row[0];

                    return $entity->getLogType() === ClientLogsView::LOG_TYPE_CLIENT_LOG;
                }
            );
            $deleteButton->setCssClasses(
                [
                    'ajax',
                    'action',
                    'action--danger',
                ],
                true
            );
        }

        $resendButton = $grid->addActionButton(
            'client_log_email_resend',
            [
                'id' => 'el_log_id',
                'client' => $client->getId(),
            ]
        );
        $resendButton->setIcon('ucrm-icon--sync');
        $resendButton->setData(
            [
                'tooltip' => $this->gridHelper->trans('Resend email'),
                'confirm' => $this->gridHelper->trans('Do you really want to resend this email?'),
                'confirm-title' => $this->gridHelper->trans('Resend email'),
                'confirm-okay' => $this->gridHelper->trans('Resend'),
            ]
        );
        $resendButton->addRenderCondition(
            function ($row) {
                /** @var ClientLogsView $entity */
                $entity = $row[0];
                if ($entity->getLogType() === ClientLogsView::LOG_TYPE_EMAIL_LOG) {
                    $emailLog = $this->entityManager->getRepository(EmailLog::class)->find($entity->getLogId());

                    return $emailLog->getStatus() === EmailLog::STATUS_ERROR
                        && ! empty(trim($emailLog->getRecipient()));
                }

                return false;
            }
        );
        $resendButton->setCssClasses(
            [
                'action',
            ],
            true
        );

        return $grid;
    }
}
