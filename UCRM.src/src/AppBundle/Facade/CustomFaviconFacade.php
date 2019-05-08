<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\FileManager\CustomFaviconFileManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CustomFaviconFacade
{
    /**
     * @var CustomFaviconFileManager
     */
    private $customFaviconFileManager;

    public function __construct(CustomFaviconFileManager $customFaviconFileManager)
    {
        $this->customFaviconFileManager = $customFaviconFileManager;
    }

    public function handleSave(UploadedFile $uploadedFile): void
    {
        $this->customFaviconFileManager->save($uploadedFile);
    }

    public function handleDelete(): void
    {
        $this->customFaviconFileManager->delete();
    }
}
