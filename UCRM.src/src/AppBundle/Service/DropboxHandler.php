<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Event\Backup\BackupDropboxConfigurationChangedEvent;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Util\Helpers;
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DropboxHandler
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    public function __construct(
        Options $options,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        OptionsFacade $optionsFacade
    ) {
        $this->options = $options;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->optionsFacade = $optionsFacade;
    }

    public function checkConnection(string $token = null): bool
    {
        if (! ($dropbox = $this->getDropbox($token))) {
            return false;
        }

        try {
            // requires a working connection to Dropbox
            $dropbox->getSpaceUsage();
        } catch (DropboxClientException $exception) {
            $this->logger->warning($exception->getMessage());

            return false;
        }

        return true;
    }

    public function requestSync(): void
    {
        if (Helpers::isDemo()) {
            return;
        }

        $this->eventDispatcher->dispatch(
            BackupDropboxConfigurationChangedEvent::class,
            new BackupDropboxConfigurationChangedEvent()
        );
    }

    public function uploadMultiple(array $files): void
    {
        if (! ($dropbox = $this->getDropbox())) {
            return;
        }

        try {
            foreach ($files as $filename) {
                $dropbox->upload($filename, '/' . basename($filename));
                $this->logger->info(sprintf('Uploaded file "%s".', $filename));
            }
            $this->optionsFacade->updateGeneral(
                General::DROPBOX_SYNC_TIMESTAMP,
                (new \DateTime())->format(\DateTime::ATOM)
            );
        } catch (DropboxClientException $exception) {
            $this->logger->warning(sprintf('Uploading of file "%s" failed.', current($files)));
            $this->logger->warning($exception->getMessage());
        }
    }

    public function delete(array $files): void
    {
        if (! $dropbox = $this->getDropbox()) {
            return;
        }

        try {
            foreach ($files as $file) {
                $dropbox->delete('/' . basename($file));
                $this->logger->info(sprintf('Deleted file "%s".', $file));
            }
            $this->optionsFacade->updateGeneral(
                General::DROPBOX_SYNC_TIMESTAMP,
                (new \DateTime())->format(\DateTime::ATOM)
            );
        } catch (DropboxClientException $exception) {
            $this->logger->warning($exception->getMessage());
        }
    }

    private function getDropbox(
        string $tokenOverride = null
    ): ?Dropbox {
        if (Helpers::isDemo()) {
            return null;
        }

        $dropboxAllowed = $tokenOverride || $this->options->get(Option::BACKUP_REMOTE_DROPBOX);
        if (! $dropboxAllowed) {
            return null;
        }

        $appToken = $tokenOverride ?? $this->options->get(Option::BACKUP_REMOTE_DROPBOX_TOKEN, '');
        if ($appToken) {
            $dropboxApp = new DropboxApp(
                '',
                '',
                $appToken
            );
            $dropbox = new Dropbox($dropboxApp);
        } else {
            $this->logger->warning('Dropbox requested, but not configured; skipping.');

            return null;
        }

        return $dropbox;
    }
}
