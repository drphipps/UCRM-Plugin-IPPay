<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Uploader;

use Nette\Utils\Strings;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ClientIdNamer implements NamerInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function name(FileInterface $file): string
    {
        $directory = Strings::replace($this->requestStack->getCurrentRequest()->get('clientId', ''), '/\//', '_');

        return rtrim(
            sprintf(
                '%s%s.%s',
                $directory ? $directory . '/' : '',
                uniqid(),
                $file->getExtension()
            ),
            '.'
        );
    }
}
