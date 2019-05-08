<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Email;

use AppBundle\Util\Helpers;
use Symfony\Component\Filesystem\Filesystem;

class EmailFilesystem
{
    /**
     * @var string
     */
    private $spoolPath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var EmailLogger
     */
    private $emailLogger;

    public function __construct(string $spoolPath, Filesystem $filesystem, EmailLogger $emailLogger)
    {
        $this->spoolPath = $spoolPath;
        $this->filesystem = $filesystem;
        $this->emailLogger = $emailLogger;
    }

    public function saveToSpool(\Swift_Message $message): string
    {
        $messageId = Helpers::getMessageId($message);
        $this->filesystem->dumpFile(
            sprintf('%s/%s', $this->spoolPath, $messageId),
            serialize($message)
        );

        return $messageId;
    }

    public function loadFromSpool(string $messageId): \Swift_Message
    {
        $emailPath = sprintf('%s/%s', $this->spoolPath, $messageId);
        if (! $this->filesystem->exists($emailPath)) {
            throw new EmailFilesystemException(sprintf('Message "%s" not found.', $messageId));
        }

        if (! is_readable($emailPath)) {
            throw new EmailFilesystemException(sprintf('Message "%s" is not readable.', $messageId));
        }

        try {
            $content = file_get_contents($emailPath);
            assert(is_string($content));
            $message = unserialize($content);

            if (! $message instanceof \Swift_Message) {
                throw new EmailFilesystemException('Message is not valid \Swift_Message.');
            }
        } catch (\Throwable $throwable) {
            throw new EmailFilesystemException(
                sprintf(
                    'Message "%s" could not be unserialized. Error: %s',
                    $messageId,
                    $throwable->getMessage()
                )
            );
        }

        return $message;
    }

    public function removeFromSpool(string $messageId): void
    {
        $path = sprintf('%s/%s', $this->spoolPath, $messageId);
        if ($this->filesystem->exists($path)) {
            $this->filesystem->remove($path);
        }
    }
}
