<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use Nette\Utils\Strings;

class Files
{
    public static function checkEncoding(string $path): bool
    {
        $file = new \SplFileObject($path, 'r');
        $isValid = true;
        while (! $file->eof()) {
            if (! Strings::checkEncoding($file->fgets())) {
                $isValid = false;

                break;
            }
        }

        $file = null;

        return $isValid;
    }
}
