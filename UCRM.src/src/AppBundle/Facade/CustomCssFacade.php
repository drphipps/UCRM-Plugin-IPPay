<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\FileManager\CustomCssFileManager;

class CustomCssFacade
{
    /**
     * @var CustomCssFileManager
     */
    private $customCssFileManager;

    public function __construct(CustomCssFileManager $customCssFileManager)
    {
        $this->customCssFileManager = $customCssFileManager;
    }

    public function handleSave(string $css): void
    {
        $this->customCssFileManager->save($css);
    }
}
