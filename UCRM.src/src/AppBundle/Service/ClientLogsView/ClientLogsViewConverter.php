<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\ClientLogsView;

use AppBundle\Component\Csv\RowData\ClientLogsViewRowData;
use AppBundle\Entity\ClientLog;
use AppBundle\Entity\ClientLogsView;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\EntityLog;
use AppBundle\Service\EntityLog\EntityLogRenderer;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

class ClientLogsViewConverter
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EntityLogRenderer
     */
    private $entityLogRenderer;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        EntityManager $entityManager,
        EntityLogRenderer $entityLogRenderer,
        Formatter $formatter,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->entityLogRenderer = $entityLogRenderer;
        $this->formatter = $formatter;
        $this->translator = $translator;
    }

    public function convertToRowDataForView(ClientLogsView $clientLogsView): ClientLogsViewRowData
    {
        $row = new ClientLogsViewRowData();
        $row->logId = $clientLogsView->getLogId();
        $row->logType = $this->convertLogType($clientLogsView);
        $row->message = $this->convertMessage($clientLogsView);
        $row->createdDate = $this->formatter->formatDate(
            $clientLogsView->getCreatedDate(),
            Formatter::DEFAULT,
            Formatter::SHORT
        );
        $row->user = $this->convertUser($clientLogsView);
        $row->entityLogDetails = $this->convertEntityLogDetails($clientLogsView);

        return $row;
    }

    private function convertLogType(ClientLogsView $clientLogsView): string
    {
        switch ($clientLogsView->getLogType()) {
            case ClientLogsView::LOG_TYPE_ENTITY_LOG:
                return $this->translator->trans('System log');
            case ClientLogsView::LOG_TYPE_CLIENT_LOG:
                return $this->translator->trans('Client log');
            case ClientLogsView::LOG_TYPE_EMAIL_LOG:
                return $this->translator->trans('Email log');
            default:
                throw new \RuntimeException('Unknown ClientLogsView logType.');
        }
    }

    private function convertMessage(ClientLogsView $clientLogsView): string
    {
        switch ($clientLogsView->getLogType()) {
            case ClientLogsView::LOG_TYPE_ENTITY_LOG:
                $entityLog = $this->entityManager->getRepository(EntityLog::class)->find($clientLogsView->getLogId());

                return $this->entityLogRenderer->renderMessage($entityLog);
            case ClientLogsView::LOG_TYPE_EMAIL_LOG:
                $emailLog = $this->entityManager->getRepository(EmailLog::class)->find($clientLogsView->getLogId());

                return sprintf(
                    '%s (%s)',
                    $emailLog->getMessage() ?? $this->translator->trans('Email in queue'),
                    $emailLog->getSubject()
                );
            case ClientLogsView::LOG_TYPE_CLIENT_LOG:
                return $clientLogsView->getMessage();
            default:
                throw new \RuntimeException('Unknown ClientLogsView logType.');
        }
    }

    private function convertUser(ClientLogsView $clientLogsView): string
    {
        switch ($clientLogsView->getLogType()) {
            case ClientLogsView::LOG_TYPE_ENTITY_LOG:
                $entityLog = $this->entityManager->getRepository(EntityLog::class)->find($clientLogsView->getLogId());

                return $entityLog->getUser()
                    ? $entityLog->getUser()->getNameForView()
                    : $this->translator->trans('System');
            case ClientLogsView::LOG_TYPE_EMAIL_LOG:
                return '';
            case ClientLogsView::LOG_TYPE_CLIENT_LOG:
                $clientLog = $this->entityManager->getRepository(ClientLog::class)->find($clientLogsView->getLogId());

                return $clientLog->getUser()
                    ? $clientLog->getUser()->getNameForView()
                    : '';
            default:
                throw new \RuntimeException('Unknown ClientLogsView logType.');
        }
    }

    private function convertEntityLogDetails(ClientLogsView $clientLogsView): string
    {
        switch ($clientLogsView->getLogType()) {
            case ClientLogsView::LOG_TYPE_ENTITY_LOG:
                $entityLog = $this->entityManager->getRepository(EntityLog::class)->find($clientLogsView->getLogId());

                return $this->entityLogRenderer->renderClientViewLogDetails($entityLog);
            default:
                return '';
        }
    }
}
