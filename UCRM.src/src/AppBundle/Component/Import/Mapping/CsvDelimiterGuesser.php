<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Mapping;

use AppBundle\Entity\Import\AbstractImport;

class CsvDelimiterGuesser
{
    public function guess(\SplFileObject $file): string
    {
        $file->rewind();

        foreach (AbstractImport::DELIMITERS as $delimiter) {
            $csv = $file->fgetcsv($delimiter);
            if ($csv && count($csv) > 1) {
                return $delimiter;
            }
        }

        return AbstractImport::DEFAULT_DELIMITER;
    }
}
