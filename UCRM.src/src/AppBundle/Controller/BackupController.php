<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Component\Uploader\BackupUploadListener;
use AppBundle\DataProvider\BackupDataProvider;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\General;
use AppBundle\Exception\BackupRestoreException;
use AppBundle\Facade\BackupFacade;
use AppBundle\Facade\OptionsFacade;
use AppBundle\FileManager\BackupFileManager;
use AppBundle\Form\Data\Settings\BackupAdditionalData;
use AppBundle\Form\Data\Settings\BackupData;
use AppBundle\Form\SettingBackupAdditionalType;
use AppBundle\Form\SettingBackupType;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\DropboxHandler;
use AppBundle\Service\OptionsManager;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use RabbitMqBundle\QueueChecker;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @Route("/system/tools/backup")
 */
class BackupController extends BaseController
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @Route("", name="backup_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="Backup", path="System -> Tools -> Backup")
     */
    public function indexAction(Request $request): Response
    {
        $formFactory = $this->get('form.factory');

        $optionsManager = $this->get(OptionsManager::class);
        /** @var BackupData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(BackupData::class);

        /** @var BackupAdditionalData $additionalOptions */
        $additionalOptions = $optionsManager->loadOptionsIntoDataClass(BackupAdditionalData::class);

        $optionsForm = $formFactory
            ->createNamedBuilder(
                'optionsForm',
                SettingBackupType::class,
                $options
            )
            ->getForm();

        $additionalOptionsForm = $formFactory
            ->createNamedBuilder(
                'additionalOptionsForm',
                SettingBackupAdditionalType::class,
                $additionalOptions
            )
            ->getForm();

        $optionsForm->handleRequest($request);
        $additionalOptionsForm->handleRequest($request);

        if ($optionsForm->isSubmitted() || $additionalOptionsForm->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

            if (Helpers::isDemo()) {
                $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

                return $this->redirectToRoute('backup_index');
            }
        }

        if ($optionsForm->isSubmitted() && $optionsForm->isValid()) {
            $optionsManager->updateOptions($options);

            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('backup_index');
        }

        if ($additionalOptionsForm->isSubmitted() && $additionalOptionsForm->isValid()) {
            if (! $additionalOptions->backupRemoteDropbox) {
                $additionalOptions->backupRemoteDropboxToken = null;
            }

            $optionsManager->updateOptions($additionalOptions);

            if (
                $additionalOptions->backupRemoteDropbox
                && $additionalOptionsForm->get('dropboxRequestSync')->isClicked()
            ) {
                $dropboxHandler = $this->get(DropboxHandler::class);
                if ($dropboxHandler->checkConnection()) {
                    $dropboxHandler->requestSync();
                    $this->addTranslatedFlash('info', 'Dropbox synchronization requested.');
                } else {
                    $this->addTranslatedFlash(
                        'error',
                        'Connection to Dropbox was not successful. Check the configuration.'
                    );
                }
            }

            if (! $additionalOptions->backupRemoteDropbox) {
                $this->get(OptionsFacade::class)->updateGeneral(General::DROPBOX_SYNC_TIMESTAMP, null);
            }

            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('backup_index');
        }

        return $this->render(
            'backup/index.html.twig',
            array_merge(
                $this->get(BackupDataProvider::class)->getViewData(),
                [
                    'optionsForm' => $optionsForm->createView(),
                    'additionalOptionsForm' => $additionalOptionsForm->createView(),
                    'areAllRabbitQueuesEmpty' => $this->get(QueueChecker::class)->areAllEmpty(),
                    'dropzoneType' => BackupUploadListener::TYPE,
                    'uploadCsrfTokenFieldName' => $this->getParameter('form.type_extension.csrf.field_name'),
                    'uploadCsrfTokenValue' => $this->csrfTokenManager
                        ->getToken(BackupUploadListener::CSRF_TOKEN_ID),
                ]
            )
        );
    }

    /**
     * @Route("/download/dropbox/test", name="backup_dropbox_test")
     * @Method({"POST"})
     * @Permission("view")
     * @CsrfToken(methods="POST")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testDropboxConnectionAction(Request $request)
    {
        if (Helpers::isDemo()) {
            $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

            return $this->createAjaxResponse();
        }

        if (! is_string($request->get('token'))) {
            throw $this->createNotFoundException();
        }

        return $this->createAjaxResponse(
            [
                'result' => $this->get(DropboxHandler::class)->checkConnection($request->get('token')),
            ]
        );
    }

    /**
     * @Route("/download/{backupFileName}", name="backup_download")
     * @Method("GET")
     * @Permission("edit")
     */
    public function backupDownloadAction(string $backupFileName, bool $isUploaded = false): Response
    {
        $path = $this->get(BackupDataProvider::class)->getBackupPath(
            $isUploaded ? BackupFileManager::BACKUP_PATH_UPLOADED : BackupFileManager::BACKUP_PATH_AUTOMATIC,
            $backupFileName
        );
        if (! $path) {
            $this->addTranslatedFlash('error', 'File does not exist.');

            return $this->redirectToRoute('backup_index');
        }

        $message['logMsg'] = [
            'message' => 'Backup file %s was downloaded.',
            'replacements' => $backupFileName,
        ];

        $this->get(ActionLogger::class)->log($message, $this->getUser(), null, EntityLog::BACKUP_DOWNLOAD);

        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $path,
            $backupFileName
        );
    }

    /**
     * @Route("/download/uploaded/{backupFileName}", name="backup_download_uploaded")
     * @Method("GET")
     * @Permission("edit")
     */
    public function backupDownloadUploadedAction(string $backupFileName): Response
    {
        return $this->backupDownloadAction($backupFileName, true);
    }

    /**
     * @Route("/download/delete/uploaded/{backupFileName}", name="backup_delete_uploaded")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function backupDeleteUploadedAction(string $backupFileName): Response
    {
        if (! $this->get(BackupFacade::class)->deleteUploadedBackup($backupFileName)) {
            $this->addTranslatedFlash('error', 'File does not exist.');
        }

        return $this->redirectToRoute('backup_index');
    }

    /**
     * @Route("/restore/{backupFileName}", name="backup_restore")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function backupRestoreAction(string $backupFileName, bool $isUploaded = false): Response
    {
        if (Helpers::isDemo()) {
            $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

            return $this->redirectToRoute('backup_index');
        }

        try {
            $this->get(BackupFacade::class)->restoreBackup(
                $isUploaded ? BackupFileManager::BACKUP_PATH_UPLOADED : BackupFileManager::BACKUP_PATH_AUTOMATIC,
                $backupFileName
            );
        } catch (BackupRestoreException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute('backup_index');
        }

        $this->addTranslatedFlash(
            'success',
            'Restore process is queued and will be processed in several minutes. UCRM application will be unavailable during recovery.'
        );

        $message['logMsg'] = [
            'message' => 'Restore from backup file %s was queued.',
            'replacements' => $backupFileName,
        ];

        $this->get(ActionLogger::class)->log($message, $this->getUser(), null, EntityLog::BACKUP_RESTORE);

        return $this->redirectToRoute('backup_index');
    }

    /**
     * @Route("/restore/uploaded/{backupFileName}", name="backup_restore_uploaded")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function backupRestoreUploadedAction(string $backupFileName): Response
    {
        return $this->backupRestoreAction($backupFileName, true);
    }

    /**
     * @Route("/create", name="backup_create")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function backupCreateAction(): Response
    {
        $this->get(BackupFacade::class)->requestAutomaticBackup();
        $this->addTranslatedFlash('success', 'Backup is queued and will be processed in several minutes.');

        return $this->redirectToRoute('backup_index');
    }
}
