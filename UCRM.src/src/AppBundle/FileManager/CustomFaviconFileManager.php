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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CustomFaviconFileManager
{
    private const ALLOWED_EXTENSIONS = [
        'png',
        'jpg',
        'jpeg',
        'gif',
        'ico',
    ];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $customFaviconDir;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    public function __construct(string $customFaviconDir, Options $options, OptionsFacade $optionsFacade)
    {
        $this->filesystem = new Filesystem();
        $this->customFaviconDir = $customFaviconDir;
        $this->options = $options;
        $this->optionsFacade = $optionsFacade;
    }

    public function save(UploadedFile $uploadedFile): void
    {
        $this->delete();

        $extension = $uploadedFile->getClientOriginalExtension() ?: 'png';
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = 'png';
        }
        $faviconFileName = sprintf(
            'favicon_%s.%s',
            md5(random_bytes(32)),
            $extension
        );
        $uploadedFile->move(
            rtrim($this->customFaviconDir, '/'),
            $faviconFileName
        );
        $this->optionsFacade->updateGeneral(General::APPEARANCE_FAVICON, $faviconFileName);
    }

    public function delete(): void
    {
        if ($path = $this->getPath()) {
            $this->optionsFacade->updateGeneral(General::APPEARANCE_FAVICON, null);
            $this->filesystem->remove($path);
        }
    }

    private function getPath(): ?string
    {
        $favicon = $this->options->getGeneral(General::APPEARANCE_FAVICON);
        if (! $favicon) {
            return null;
        }

        return rtrim($this->customFaviconDir, '/') . '/' . $favicon;
    }
}
