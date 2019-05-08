<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use AppBundle\Entity\PaymentReceiptTemplate;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class PaymentReceiptTemplateFileManager
{
    public const TWIG_FILENAME = 'template.html.twig';
    public const CSS_FILENAME = 'template.css';

    /**
     * @var string
     */
    private $customTemplatePath;

    /**
     * @var string
     */
    private $officialTemplatePath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        string $customTemplatePath,
        string $officialTemplatePath
    ) {
        $this->customTemplatePath = $customTemplatePath;
        $this->officialTemplatePath = $officialTemplatePath;
        $this->filesystem = new Filesystem();
    }

    public function getSource(PaymentReceiptTemplate $template, string $file): string
    {
        $path = $this->getPath($template, $file);
        if (! $this->filesystem->exists($path)) {
            throw new FileNotFoundException(sprintf('Template file could not be found in "%s".', $path));
        }

        return file_get_contents($path);
    }

    public function existsTwig(PaymentReceiptTemplate $template): bool
    {
        return $this->filesystem->exists($this->getPath($template, self::TWIG_FILENAME));
    }

    public function saveTwig(PaymentReceiptTemplate $template, string $content): void
    {
        $this->checkOfficial($template);

        $this->filesystem->dumpFile(
            $this->getCustomPath($template, self::TWIG_FILENAME),
            $content
        );
    }

    public function saveCss(PaymentReceiptTemplate $template, string $content): void
    {
        $this->checkOfficial($template);

        $this->filesystem->dumpFile(
            $this->getCustomPath($template, self::CSS_FILENAME),
            $content
        );
    }

    public function delete(PaymentReceiptTemplate $template): void
    {
        $this->filesystem->remove($this->getCustomPath($template, null));
    }

    public function clone(PaymentReceiptTemplate $origin, PaymentReceiptTemplate $target): void
    {
        $this->filesystem->mirror(
            $this->getPath($origin, null),
            $this->getCustomPath($target, null),
            null,
            [
                'override' => true,
                'delete' => true,
            ]
        );
    }

    private function checkOfficial(PaymentReceiptTemplate $template): void
    {
        if ($template->getOfficialName()) {
            throw new \InvalidArgumentException('Not supported for official templates.');
        }
    }

    private function getPath(PaymentReceiptTemplate $template, ?string $file): string
    {
        if ($template->getOfficialName()) {
            return $this->getOfficialPath($template, $file);
        }

        return $this->getCustomPath($template, $file);
    }

    private function getCustomPath(PaymentReceiptTemplate $template, ?string $file): string
    {
        return implode(
            '/',
            array_filter(
                [
                    $this->customTemplatePath,
                    $template->getId(),
                    $file,
                ]
            )
        );
    }

    private function getOfficialPath(PaymentReceiptTemplate $template, ?string $file): string
    {
        return implode(
            '/',
            array_filter(
                [
                    $this->officialTemplatePath,
                    $template->getOfficialName(),
                    $file,
                ]
            )
        );
    }
}
