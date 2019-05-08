<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"IsUploadedFile", "TemplateImportData"})
 */
class TemplateImportData
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
     * @Assert\File(
     *     maxSize = "50M",
     *     mimeTypes = {"application/zip"},
     *     mimeTypesMessage = "Please upload a valid ZIP archive."
     * )
     */
    public $file;

    /**
     * @var string|null
     *
     * @Assert\Length(max=255)
     * @Assert\NotNull()
     */
    public $name;
}
