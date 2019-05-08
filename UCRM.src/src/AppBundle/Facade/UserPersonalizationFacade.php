<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\ClientLogsView;
use AppBundle\Entity\UserPersonalization;
use Doctrine\ORM\EntityManagerInterface;

class UserPersonalizationFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getVisibleClientLogs(UserPersonalization $userPersonalization): array
    {
        $clientLogsView = [];
        if ($userPersonalization->isClientShowClientLog()) {
            $clientLogsView[] = ClientLogsView::LOG_TYPE_CLIENT_LOG;
        }
        if ($userPersonalization->isClientShowEmailLog()) {
            $clientLogsView[] = ClientLogsView::LOG_TYPE_EMAIL_LOG;
        }
        if ($userPersonalization->isClientShowSystemLog()) {
            $clientLogsView[] = ClientLogsView::LOG_TYPE_ENTITY_LOG;
        }

        return $clientLogsView;
    }

    public function setVisibleClientLogs(UserPersonalization $userPersonalization, array $visibleLogs): void
    {
        $userPersonalization->setClientShowClientLog(in_array(ClientLogsView::LOG_TYPE_CLIENT_LOG, $visibleLogs, true));
        $userPersonalization->setClientShowSystemLog(in_array(ClientLogsView::LOG_TYPE_ENTITY_LOG, $visibleLogs, true));
        $userPersonalization->setClientShowEmailLog(in_array(ClientLogsView::LOG_TYPE_EMAIL_LOG, $visibleLogs, true));

        $this->handleEdit($userPersonalization);
    }

    public function handleEdit(UserPersonalization $userPersonalization): void
    {
        $this->em->flush();
    }
}
