<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Option;
use AppBundle\Facade\CertificateFacade;
use AppBundle\Form\Data\CertificateUploadData;
use AppBundle\Service\Options;
use Nette\Utils\Strings;
use Symfony\Component\Filesystem\Filesystem;

class CertificateDataProvider
{
    private const SERVER_CONTROL_LOG_PATH = '/letsencrypt_logs/server_control.log';
    private const LETS_ENCRYPT_LOG_PATH = '/letsencrypt_logs/letsencrypt.log';
    private const LETS_ENCRYPT_PATH = '/data/ssl/letsencrypt/';
    private const LETS_ENCRYPT_CERT_FILE_NAME = 'fullchain.pem';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $customPath;

    /**
     * @var string
     */
    private $letsEncryptPath;

    /**
     * @var string
     */
    private $iniPath;

    /**
     * @var string
     */
    private $useCustomPath;

    /**
     * @var string
     */
    private $useLetsEncryptPath;

    /**
     * @var string
     */
    private $serverControlLogPath;

    /**
     * @var string
     */
    private $letsEncryptLogPath;

    /**
     * @var Options
     */
    private $options;

    public function __construct(string $rootDir, Options $options)
    {
        $this->rootDir = $rootDir;
        $this->filesystem = new Filesystem();
        $this->customPath = $this->rootDir . CertificateFacade::CUSTOM_PATH;
        $this->letsEncryptPath = $this->rootDir . self::LETS_ENCRYPT_PATH;
        $this->iniPath = $this->rootDir . CertificateFacade::INI_PATH;
        $this->useCustomPath = $this->rootDir . CertificateFacade::USE_CUSTOM_PATH;
        $this->useLetsEncryptPath = $this->rootDir . CertificateFacade::USE_LETS_ENCRYPT_PATH;
        $this->serverControlLogPath = $this->rootDir . self::SERVER_CONTROL_LOG_PATH;
        $this->letsEncryptLogPath = $this->rootDir . self::LETS_ENCRYPT_LOG_PATH;
        $this->options = $options;
    }

    public function getCustomExpiration(): ?\DateTimeImmutable
    {
        $certPath = $this->customPath . CertificateUploadData::CERT_FILE_NAME;
        if (! $this->filesystem->exists($certPath)) {
            return null;
        }

        return $this->getValidToFromCertificateInfo(file_get_contents($certPath));
    }

    public function getLetsEncryptExpiration(): ?\DateTimeImmutable
    {
        $certPath = $this->letsEncryptPath . self::LETS_ENCRYPT_CERT_FILE_NAME;
        if (! $this->filesystem->exists($certPath)) {
            return null;
        }

        return $this->getValidToFromCertificateInfo(file_get_contents($certPath));
    }

    public function isCustomEnabled(): bool
    {
        return $this->filesystem->exists($this->useCustomPath);
    }

    public function isLetsEncryptEnabled(): bool
    {
        return $this->filesystem->exists($this->useLetsEncryptPath);
    }

    public function getLetsEncryptEmail(): ?string
    {
        if (! $this->filesystem->exists($this->iniPath)) {
            return null;
        }

        $match = Strings::match(file_get_contents($this->iniPath), '/^email = (.+)$/m');

        return $match[1] ?? null;
    }

    public function getServerControlLog(): ?string
    {
        return $this->filesystem->exists($this->serverControlLogPath)
            ? file_get_contents($this->serverControlLogPath)
            : null;
    }

    public function getLetsEncryptLog(): ?string
    {
        return $this->filesystem->exists($this->letsEncryptLogPath)
            ? file_get_contents($this->letsEncryptLogPath)
            : null;
    }

    private function getValidToFromCertificateInfo(string $certificate): ?\DateTimeImmutable
    {
        $info = openssl_x509_parse($certificate, true);
        if (! is_array($info) || ! array_key_exists('validTo_time_t', $info)) {
            return null;
        }

        return (new \DateTimeImmutable('@' . $info['validTo_time_t']))
            ->setTimezone(new \DateTimeZone($this->options->get(Option::APP_TIMEZONE)));
    }
}
