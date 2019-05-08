<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Service;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class Encryption
{
    const KEY_FILE_PATH = '/data/encryption/crypto.key';
    const ERROR_PASSWORD = 'encrypted UCRM error message for developers only';

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns saved Crypto key or null if does not exist.
     *
     * @return Key|null
     */
    public function getKey()
    {
        $file = $this->getPath();
        if (file_exists($file)) {
            return Key::loadFromAsciiSafeString(file_get_contents($file));
        }

        return $this->generateKey();
    }

    /**
     * Creates new random Crypto key and saves to file.
     *
     * @return Key|null
     */
    public function generateKey()
    {
        if (! $this->doesKeyExist()) {
            $fs = new Filesystem();
            $key = Key::createNewRandomKey();
            $fs->dumpFile($this->getPath(), $key->saveToAsciiSafeString());
        }

        return $key ?? null;
    }

    public function doesKeyExist(): bool
    {
        return file_exists($this->getPath());
    }

    /**
     * @return string
     */
    private function getPath()
    {
        return $this->container->getParameter('kernel.root_dir') . self::KEY_FILE_PATH;
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public function encrypt($data)
    {
        if (strlen($data) > 0) {
            return Crypto::encrypt($data, $this->getKey());
        }

        return $data;
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function decrypt(?string $data): ?string
    {
        if (is_string($data) && strlen($data) > 0) {
            return Crypto::decrypt($data, $this->getKey());
        }

        return $data;
    }

    /**
     * @return string
     */
    public function encryptError(FlattenException $exception)
    {
        $date = new \DateTime();
        $error = sprintf(
            "Version: %s\nTimestamp: %s\nException: %s\nFile: %s:%s\nMessage: %s\nCode: %s",
            $this->container->getParameter('version'),
            $date->format('c'),
            $exception->getClass(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getMessage(),
            $exception->getCode()
        );

        if ($exception->getPrevious()) {
            $error .= sprintf("\nPrevious: %s", $exception->getPrevious()->getClass());
        }

        return base64_encode(Crypto::encryptWithPassword($error, self::ERROR_PASSWORD));
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public function decryptError($data)
    {
        return Crypto::decryptWithPassword(base64_decode($data), self::ERROR_PASSWORD);
    }
}
