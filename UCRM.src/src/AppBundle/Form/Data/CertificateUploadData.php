<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Assert\GroupSequence({"IsUploadedFile", "CertificateUploadData"})
 */
class CertificateUploadData
{
    public const CERT_FILE_NAME = 'ucrm.crt';
    public const KEY_FILE_NAME = 'ucrm.key';

    /**
     * Type constraint for UploadedFile must be used to prevent file enumeration attack.
     *
     * @Assert\Type(
     *     type="\Symfony\Component\HttpFoundation\File\UploadedFile",
     *     groups={"IsUploadedFile"},
     *     message="Uploaded file is not valid."
     * )
     * @Assert\File(maxSize = "2M")
     *
     * @var UploadedFile|null
     */
    public $certFile;

    /**
     * Type constraint for UploadedFile must be used to prevent file enumeration attack.
     *
     * @Assert\Type(
     *     type="\Symfony\Component\HttpFoundation\File\UploadedFile",
     *     groups={"IsUploadedFile"},
     *     message="Uploaded file is not valid."
     * )
     * @Assert\File(maxSize = "2M")
     *
     * @var UploadedFile|null
     */
    public $caBundleFile;

    /**
     * Type constraint for UploadedFile must be used to prevent file enumeration attack.
     *
     * @Assert\Type(
     *     type="\Symfony\Component\HttpFoundation\File\UploadedFile",
     *     groups={"IsUploadedFile"},
     *     message="Uploaded file is not valid."
     * )
     * @Assert\File(maxSize = "2M")
     *
     * @var UploadedFile|null
     */
    public $keyFile;

    /**
     * @Assert\Callback()
     */
    public function validate(ExecutionContextInterface $context): void
    {
        if (! $this->isValid()) {
            $context
                ->buildViolation(
                    'Certificate files are invalid.'
                )
                ->atPath('certFile')
                ->addViolation();
        }
    }

    private function isValid(): bool
    {
        $cert = $this->getCertificateBundle();
        if (! $cert || ! $this->keyFile) {
            return false;
        }
        $key = file_get_contents($this->keyFile->getRealPath());

        return openssl_x509_check_private_key($cert, $key);
    }

    public function getCertificateBundle(): ?string
    {
        if (! $this->certFile) {
            return null;
        }

        $cert = file_get_contents($this->certFile->getRealPath());
        if ($this->caBundleFile) {
            $cert .= PHP_EOL . file_get_contents($this->caBundleFile->getRealPath());
        }

        return $cert;
    }
}
