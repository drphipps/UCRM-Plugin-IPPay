<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class PluginUploadData
{
    /**
     * @var UploadedFile|null
     * @Assert\File(
     *     maxSize = "50M",
     *     mimeTypes = {"application/zip"},
     *     mimeTypesMessage = "Please upload a valid ZIP archive."
     * )
     * @Assert\NotNull(message="File is invalid.")
     */
    public $pluginFile;
}
