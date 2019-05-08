<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\FileManager\LoginBannerFileManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LoginBannerFacade
{
    /**
     * @var LoginBannerFileManager
     */
    private $loginBannerFileManager;

    public function __construct(LoginBannerFileManager $loginBannerFileManager)
    {
        $this->loginBannerFileManager = $loginBannerFileManager;
    }

    public function handleSave(UploadedFile $uploadedFile): void
    {
        $this->loginBannerFileManager->save($uploadedFile);
    }

    public function handleDelete(): void
    {
        $this->loginBannerFileManager->delete();
    }
}
