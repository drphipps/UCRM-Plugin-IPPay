<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use AppBundle\Service\CssSanitizer;
use Nette\Utils\Strings;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class CustomCssFileManager
{
    /**
     * @var string
     */
    private $customCssPath;

    /**
     * @var string
     */
    private $customCssPublicPath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var CssSanitizer
     */
    private $cssSanitizer;

    public function __construct(string $customCssPath, string $customCssPublicPath, CssSanitizer $cssSanitizer)
    {
        $this->customCssPath = $customCssPath;
        $this->customCssPublicPath = $customCssPublicPath;
        $this->filesystem = new Filesystem();
        $this->cssSanitizer = $cssSanitizer;
    }

    public function get(): string
    {
        if (! $this->filesystem->exists($this->customCssPath)) {
            return '';
        }

        return file_get_contents($this->customCssPath) ?: '';
    }

    public function getSanitizedHash(): ?string
    {
        if (! $this->filesystem->exists($this->customCssPublicPath)) {
            return null;
        }

        return md5((string) (new File($this->customCssPublicPath))->getMTime());
    }

    public function save(string $css): void
    {
        if (Strings::trim($css) === '') {
            $this->filesystem->remove(
                [
                    $this->customCssPath,
                    $this->customCssPublicPath,
                ]
            );
        } else {
            $this->filesystem->dumpFile(
                $this->customCssPath,
                $css
            );
            $this->filesystem->dumpFile(
                $this->customCssPublicPath,
                $this->cssSanitizer->sanitize($css)
            );
        }
    }
}
