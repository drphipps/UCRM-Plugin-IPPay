<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Form\Data\CertificateUploadData;
use AppBundle\Util\Helpers;
use Symfony\Component\Filesystem\Filesystem;

class CertificateFacade
{
    public const CUSTOM_PATH = '/data/ssl/';
    public const INI_PATH = '/data/ssl/certbot.ini';
    public const USE_LETS_ENCRYPT_PATH = '/data/ssl/.use_lets_encrypt';
    public const USE_CUSTOM_PATH = '/data/ssl/.use_custom';

    private const CUSTOM_CERT_FILE_NAME = 'ucrm.crt';
    private const CUSTOM_KEY_FILE_NAME = 'ucrm.key';
    private const INI_TEMPLATE_PATH = '/internal/certbot/template.ini';
    private const RUN_SERVER_CONTROL_PATH = '/data/ssl/.server_control_run';

    private const EMAIL_REPLACE_KEY = '%ucrm.replace.email%';

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $iniTemplatePath;

    /**
     * @var string
     */
    private $iniPath;

    /**
     * @var string
     */
    private $runPath;

    /**
     * @var string
     */
    private $customPath;

    /**
     * @var string
     */
    private $useCustomPath;

    /**
     * @var string
     */
    private $useLetsEncryptPath;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->filesystem = new Filesystem();
        $this->iniTemplatePath = $this->rootDir . self::INI_TEMPLATE_PATH;
        $this->iniPath = $this->rootDir . self::INI_PATH;
        $this->runPath = $this->rootDir . self::RUN_SERVER_CONTROL_PATH;
        $this->customPath = $this->rootDir . self::CUSTOM_PATH;
        $this->useCustomPath = $this->rootDir . self::USE_CUSTOM_PATH;
        $this->useLetsEncryptPath = $this->rootDir . self::USE_LETS_ENCRYPT_PATH;
    }

    public function enableLetsEncrypt(string $email): void
    {
        $iniTemplate = file_get_contents($this->iniTemplatePath);
        $ini = strtr(
            $iniTemplate,
            [
                self::EMAIL_REPLACE_KEY => $email,
            ]
        );
        $this->filesystem->dumpFile($this->iniPath, $ini);

        $this->filesystem->remove($this->useCustomPath);
        $this->filesystem->touch($this->useLetsEncryptPath);
        $this->runServerControl();
    }

    public function disableLetsEncrypt(): void
    {
        $this->filesystem->remove(
            [
                $this->iniPath,
                $this->useLetsEncryptPath,
            ]
        );

        if ($this->filesystem->exists(
            [
                $this->customPath . CertificateUploadData::CERT_FILE_NAME,
                $this->customPath . CertificateUploadData::KEY_FILE_NAME,
            ]
        )
        ) {
            $this->filesystem->touch($this->useCustomPath);
        }

        $this->runServerControl();
    }

    public function enableCustom(): void
    {
        $this->filesystem->remove($this->useLetsEncryptPath);
        $this->filesystem->touch($this->useCustomPath);
        $this->runServerControl();
    }

    public function disableCustom(): void
    {
        $this->filesystem->remove(
            [
                $this->useCustomPath,
                $this->customPath . CertificateUploadData::CERT_FILE_NAME,
                $this->customPath . CertificateUploadData::KEY_FILE_NAME,
            ]
        );

        $this->runServerControl();
    }

    public function runServerControl(): void
    {
        if (Helpers::isDemo()) {
            return;
        }

        $this->filesystem->touch($this->runPath);
    }

    public function handleCustomUpload(CertificateUploadData $certificateUploadData): bool
    {
        if (null === $certificateUploadData->certFile || null === $certificateUploadData->keyFile) {
            return false;
        }

        $certPath = $this->customPath . self::CUSTOM_CERT_FILE_NAME;
        $keyPath = $this->customPath . self::CUSTOM_KEY_FILE_NAME;

        if ($this->filesystem->exists($certPath)) {
            $this->filesystem->remove($certPath . '.bak');
            $this->filesystem->rename($certPath, $certPath . '.bak');
        }
        if ($this->filesystem->exists($keyPath)) {
            $this->filesystem->remove($keyPath . '.bak');
            $this->filesystem->rename($keyPath, $keyPath . '.bak');
        }

        if (! $certificateUploadData->getCertificateBundle()) {
            return false;
        }

        $this->filesystem->dumpFile($certPath, $certificateUploadData->getCertificateBundle());
        $this->filesystem->remove($certificateUploadData->certFile->getRealPath());
        $certificateUploadData->keyFile->move($this->customPath, self::CUSTOM_KEY_FILE_NAME);

        return true;
    }
}
