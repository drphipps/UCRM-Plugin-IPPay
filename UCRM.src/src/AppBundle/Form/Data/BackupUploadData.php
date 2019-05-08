<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"IsUploadedFile", "BackupUploadData"})
 */
class BackupUploadData
{
    /**
     * @var UploadedFile|null
     *
     * Type constraint for UploadedFile must be used to prevent file enumeration attack
     *
     * @Assert\Type(
     *     type="\Symfony\Component\HttpFoundation\File\UploadedFile",
     *     groups={"IsUploadedFile"},
     *     message="Uploaded file is not valid."
     * )
     * @Assert\File()
     * @Assert\NotNull(message="File is invalid.")
     * @CustomAssert\BackupFileName()
     */
    public $backupFile;
}
