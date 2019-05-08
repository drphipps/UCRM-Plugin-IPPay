<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use AppBundle\Entity\General;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Service\Options;
use AppBundle\Util\Helpers;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LoginBannerFileManager
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $loginBannerDir;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    public function __construct(string $rootDir, Options $options, OptionsFacade $optionsFacade, Packages $packages)
    {
        $this->filesystem = new Filesystem();
        $this->loginBannerDir = sprintf(
            '%s/../web/%s',
            $rootDir,
            ltrim($packages->getUrl('', 'loginBanner'), '/')
        );
        $this->options = $options;
        $this->optionsFacade = $optionsFacade;
    }

    public function save(UploadedFile $uploadedFile): void
    {
        $this->delete();
        $fileName = Helpers::getUniqueFileName($uploadedFile);

        $uploadedFile->move(
            rtrim($this->loginBannerDir, '/'),
            $fileName
        );
        $this->optionsFacade->updateGeneral(General::APPEARANCE_LOGIN_BANNER, $fileName);
    }

    public function delete(): void
    {
        if ($path = $this->getPath()) {
            $this->optionsFacade->updateGeneral(General::APPEARANCE_LOGIN_BANNER, null);
            $this->filesystem->remove($path);
        }
    }

    private function getPath(): ?string
    {
        $loginBanner = $this->options->getGeneral(General::APPEARANCE_LOGIN_BANNER);
        if (! $loginBanner) {
            return null;
        }

        return rtrim($this->loginBannerDir, '/') . '/' . $loginBanner;
    }
}
