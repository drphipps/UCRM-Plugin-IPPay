<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Uploader;

use AppBundle\Controller\DocumentController;
use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use AppBundle\Facade\DocumentFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Genedys\CsrfRouteBundle\Handler\TokenHandlerInterface;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Event\PreUploadEvent;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DocumentsUploadListener
{
    public const ARG_CLIENT_ID = 'clientId';
    public const CSRF_TOKEN_ID = 'DocumentsUploadListener';
    public const TYPE = 'documents';

    /**
     * @var string
     */
    private $csrfFieldName;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var DocumentFacade
     */
    private $documentFacade;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string|null
     */
    private $originalFilename;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var TokenHandlerInterface
     */
    private $tokenHandler;

    public function __construct(
        string $csrfFieldName,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        DocumentFacade $documentFacade,
        PermissionGrantedChecker $permissionGrantedChecker,
        TranslatorInterface $translator,
        Filesystem $filesystem,
        TokenHandlerInterface $tokenHandler
    ) {
        $this->csrfFieldName = $csrfFieldName;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->documentFacade = $documentFacade;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
        $this->translator = $translator;
        $this->filesystem = $filesystem;
        $this->tokenHandler = $tokenHandler;
    }

    public function preUpload(PreUploadEvent $event): void
    {
        if (! $this->tokenHandler->isTokenValid(self::CSRF_TOKEN_ID, $event->getRequest()->get($this->csrfFieldName))) {
            throw new UploadException('Invalid CSRF token.');
        }

        $this->originalFilename = $event->getFile()->getClientOriginalName();
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
        if (! $this->permissionGrantedChecker->isGranted(Permission::EDIT, DocumentController::class)) {
            throw new UploadException($this->translator->trans('You do not have permission to upload documents.'));
        }

        $clientId = $event->getRequest()->get(self::ARG_CLIENT_ID);
        if (! $clientId) {
            throw new UploadException($this->translator->trans('Client not found.'));
        }

        $client = $this->em->find(Client::class, $clientId);
        if (! $client || $client->isDeleted()) {
            throw new UploadException($this->translator->trans('Client not found.'));
        }

        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();
        if (! $token || ! $user instanceof User) {
            throw new UploadException($this->translator->trans('You must be logged in to upload documents.'));
        }

        if (Helpers::isDemo()) {
            throw new UploadException($this->translator->trans('File upload is is not available in the demo.'));
        }

        $this->documentFacade->handleNew(
            $client,
            $user,
            $event->getFile(),
            $this->originalFilename
        );
        $this->originalFilename = null;

        $response = $event->getResponse();
        $response['success'] = true;

        return $response;
    }
}
