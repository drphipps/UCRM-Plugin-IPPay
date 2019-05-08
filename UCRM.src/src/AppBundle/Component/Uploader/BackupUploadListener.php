<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Uploader;

use AppBundle\Component\Validator\Constraints\BackupFileName;
use AppBundle\Controller\BackupController;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\User;
use AppBundle\Facade\BackupFacade;
use AppBundle\FileManager\BackupFileManager;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Service\ActionLogger;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Handler\TokenHandlerInterface;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Event\PreUploadEvent;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BackupUploadListener
{
    public const CSRF_TOKEN_ID = 'BackupUploadListener';
    public const TYPE = 'backups';

    /**
     * @var string
     */
    private $csrfFieldName;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var TokenHandlerInterface
     */
    private $tokenHandler;

    /**
     * @var BackupFacade
     */
    private $backupFacade;

    /**
     * @var ActionLogger
     */
    private $actionLogger;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var BackupFileManager
     */
    private $backupFileManager;

    public function __construct(
        string $csrfFieldName,
        TokenStorageInterface $tokenStorage,
        PermissionGrantedChecker $permissionGrantedChecker,
        TranslatorInterface $translator,
        Filesystem $filesystem,
        TokenHandlerInterface $tokenHandler,
        BackupFacade $backupFacade,
        ActionLogger $actionLogger,
        ValidatorInterface $validator,
        BackupFileManager $backupFileManager
    ) {
        $this->csrfFieldName = $csrfFieldName;
        $this->tokenStorage = $tokenStorage;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
        $this->translator = $translator;
        $this->filesystem = $filesystem;
        $this->tokenHandler = $tokenHandler;
        $this->backupFacade = $backupFacade;
        $this->actionLogger = $actionLogger;
        $this->validator = $validator;
        $this->backupFileManager = $backupFileManager;
    }

    public function preUpload(PreUploadEvent $event): void
    {
        if (! $this->tokenHandler->isTokenValid(self::CSRF_TOKEN_ID, $event->getRequest()->get($this->csrfFieldName))) {
            throw new UploadException('Invalid CSRF token.');
        }
    }

    public function onUpload(PostPersistEvent $event): ResponseInterface
    {
        try {
            return $this->processUpload($event);
        } catch (\Throwable $throwable) {
            $this->filesystem->remove($event->getFile()->getRealPath());

            throw $throwable;
        }
    }

    private function processUpload(PostPersistEvent $event): ResponseInterface
    {
        if (! $this->permissionGrantedChecker->isGranted(Permission::EDIT, BackupController::class)) {
            throw new UploadException($this->translator->trans('You do not have permission to upload backup.'));
        }

        if (Helpers::isDemo()) {
            throw new UploadException($this->translator->trans('File upload is is not available in the demo.'));
        }

        /** @var File $file */
        $file = $event->getFile();
        $errors = $this->validator->validate($file, new BackupFileName());

        if (count($errors)) {
            throw new UploadException($errors[0]->getMessage());
        }

        $fileName = $this->backupFileManager->handleBackupFile($file);

        $message['logMsg'] = [
            'message' => 'Backup file %s was uploaded',
            'replacements' => $fileName,
        ];

        $token = $this->tokenStorage->getToken();
        $this->actionLogger->log(
            $message,
            $token->getUser() instanceof User ? $token->getUser() : null,
            null,
            EntityLog::BACKUP_UPLOAD
        );

        $response = $event->getResponse();
        $response['success'] = true;

        return $response;
    }
}
