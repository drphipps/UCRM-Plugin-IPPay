<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Imagine;

use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Nette\Utils\Strings;
use Symfony\Component\Filesystem\Filesystem;

class ImagineDataFileProvider
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var DataManager
     */
    private $dataManager;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var FilesystemResolver
     */
    private $resolver;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        string $rootDir,
        CacheManager $cacheManager,
        DataManager $dataManager,
        FilterManager $filterManager,
        FilesystemResolver $resolver,
        Filesystem $filesystem
    ) {
        $this->rootDir = $rootDir;
        $this->cacheManager = $cacheManager;
        $this->dataManager = $dataManager;
        $this->filterManager = $filterManager;
        $this->resolver = $resolver;
        $this->filesystem = $filesystem;
    }

    public function getDataUri(string $path, ?string $filter): string
    {
        $image = $this->getImageFileContent($path, $filter);
        if ($image === '') {
            return '';
        }

        return $this->convertToDataUri($image);
    }

    public function getImageFileContent(string $path, ?string $filter = null): string
    {
        $filePath = $this->getImageFilePath($path, $filter);

        if (null !== $filePath) {
            // we have the image stored, either the path points directly to it, or we have thumbnail in cache

            if (! file_exists($filePath)) {
                return '';
            }

            return file_get_contents($filePath);
        }
        if (null !== $filter) {
            // we want image thumbnail and we don't have it already in cache

            try {
                // find original image
                $binary = $this->dataManager->find($filter, $path);
            } catch (NotLoadableException $e) {
                return '';
            }

            // apply requested imagine filter (e.g. thumbnail) to the original
            $thumbnail = $this->filterManager->applyFilter($binary, $filter);

            // save the thumbnail to imagine cache
            $this->cacheManager->store(
                $thumbnail,
                $path,
                $filter
            );

            return $thumbnail->getContent();
        }

        // image does not exist, but we don't want to crash here
        return '';
    }

    private function getImageFilePath(string $path, ?string $filter): ?string
    {
        $filePath = null;
        if (Strings::startsWith($path, $this->rootDir)) {
            // file path already points to either original file or stored thumbnail
            $filePath = $path;
        } elseif (null === $filter) {
            // we want original image and have only relative path stored
            $filePath = sprintf('%s/../web/%s', rtrim($this->rootDir, '/'), ltrim($path, '/'));
        } elseif ($this->cacheManager->isStored(ltrim($path, '/'), $filter)) {
            // we want thumbnail image, if it's already created we want it from cache
            $filePath = $this->resolver->getFilePath(ltrim($path, '/'), $filter);
        }

        return $filePath;
    }

    private function convertToDataUri(string $image): string
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->buffer($image);

        $params = [];
        $isBinaryData = strpos($mimeType, 'text/') !== 0;
        if (null === $mimeType) {
            $mimeType = 'text/plain';
            $params['charset'] = 'UTF-8';
        }
        $parameters = '';
        if (0 !== count($params)) {
            foreach ($params as $paramName => $paramValue) {
                $parameters .= sprintf(';%s=%s', $paramName, $paramValue);
            }
        }
        $base64 = '';
        if ($isBinaryData) {
            $base64 = sprintf(';%s', 'base64');
            $dataURI = base64_encode($image);
        } else {
            $dataURI = rawurlencode($image);
        }

        return sprintf('data:%s%s%s,%s', $mimeType, $parameters, $base64, $dataURI);
    }
}
