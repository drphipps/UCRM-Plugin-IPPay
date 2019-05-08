<?php

namespace AppBundle\Component\Imagine;

use Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver;

class FilesystemResolver extends WebPathResolver
{
    /**
     * @param string $path
     * @param string $filter
     */
    public function getFilePath($path, $filter): string
    {
        return parent::getFilePath($path, $filter);
    }
}
