<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"IsUploadedFile", "LoginBannerUploadData"})
 */
class LoginBannerUploadData
{
    /**
     * Type constraint for UploadedFile must be used to prevent file enumeration attack.
     *
     * @Assert\Type(
     *     type="\Symfony\Component\HttpFoundation\File\UploadedFile",
     *     groups={"IsUploadedFile"},
     *     message="Uploaded file is not valid."
     * )
     * @Assert\Image(
     *     mimeTypes = {"image/jpeg", "image/jpg", "image/gif", "image/png"},
     *     mimeTypesMessage = "Image must be in JPEG, PNG or GIF format.",
     *     maxSize = "4M"
     * )
     */
    public $loginBanner;
}
