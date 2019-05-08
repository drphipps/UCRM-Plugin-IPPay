<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\FileManager\WebrootFileManager;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class WebrootUploadFacade
{
    /**
     * @var WebrootFileManager
     */
    private $webrootUploadFileManager;

    public function __construct(WebrootFileManager $webrootUploadFileManager)
    {
        $this->webrootUploadFileManager = $webrootUploadFileManager;
    }

    /**
     * @throws FileException
     */
    public function handleWebrootUpload(UploadedFile $file): string
    {
        return $this->webrootUploadFileManager->handleWebrootUpload($file);
    }
}
