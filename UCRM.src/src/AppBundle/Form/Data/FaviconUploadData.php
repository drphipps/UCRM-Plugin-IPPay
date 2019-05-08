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
 * @Assert\GroupSequence({"IsUploadedFile", "FaviconUploadData"})
 */
class FaviconUploadData
{
    /**
     * @var UploadedFile
     *
     * Type constraint for UploadedFile must be used to prevent file enumeration attack
     *
     * @Assert\Type(
     *     type="\Symfony\Component\HttpFoundation\File\UploadedFile",
     *     groups={"IsUploadedFile"},
     *     message="Uploaded file is not valid."
     * )
     * @Assert\Image(
     *     mimeTypes = {"image/jpeg", "image/jpg", "image/gif", "image/png", "image/x-icon"},
     *     mimeTypesMessage = "Image must be in JPEG, PNG, GIF or ICO format.",
     *     maxSize = "4M"
     * )
     * @Assert\NotNull()
     */
    public $favicon;
}
