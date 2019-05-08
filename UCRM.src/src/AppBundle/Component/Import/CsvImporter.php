<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import;

use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @deprecated Client CSV import was refactored, this class is obsolete and only used for Payment import, which is not yet refactored
 * @see https://ubnt.myjetbrains.com/youtrack/issue/UCRM-2807
 */
class CsvImporter
{
    public const DEFAULT_DELIMITER = ',';
    public const DEFAULT_ENCLOSURE = '"';
    public const DEFAULT_ESCAPE = '\\';
    public const FIELD_AUTO_DETECT_STRUCTURE = 'auto_detect_structure';
    public const FIELD_DELIMITER = 'delimiter';
    public const FIELD_ENCLOSURE = 'enclosure';
    public const FIELD_ESCAPE = 'escape';
    public const FIELD_HAS_HEADER = 'has_header';
    public const IMPORT_DIR = '/data/import/csv/';

    private const DELIMITERS = [
        ',',
        ';',
        "\t",
        '|',
        '^',
    ];

    /**
     * @var File
     */
    private $file;

    /**
     * @var array
     */
    private $csvControl = [
        self::FIELD_DELIMITER => self::DEFAULT_DELIMITER,
        self::FIELD_ENCLOSURE => self::DEFAULT_ENCLOSURE,
        self::FIELD_ESCAPE => self::DEFAULT_ESCAPE,
    ];

    /**
     * @deprecated
     */
    public function __construct(
        File $file,
        array $ctrl
    ) {
        $this->file = $file;

        if ($ctrl[self::FIELD_AUTO_DETECT_STRUCTURE]) {
            $this->guessDelimiter();
        } else {
            $this->csvControl = [
                self::FIELD_DELIMITER => $ctrl[self::FIELD_DELIMITER],
                self::FIELD_ENCLOSURE => $ctrl[self::FIELD_ENCLOSURE],
                self::FIELD_ESCAPE => Strings::length($ctrl[self::FIELD_ESCAPE]) > 0
                    ? $ctrl[self::FIELD_ESCAPE]
                    : self::DEFAULT_ESCAPE,
            ];
        }
    }

    /**
     * @deprecated
     */
    public function setCsvControl(array $csvControl): void
    {
        $this->csvControl = $csvControl;
    }

    /**
     * @deprecated
     */
    public function getCsvControl(): array
    {
        return $this->csvControl;
    }

    private function guessDelimiter(): void
    {
        foreach (self::DELIMITERS as $delimiter) {
            $csv = $this->file->openFile()->fgetcsv($delimiter);
            if ($csv && count($csv) > 1) {
                $this->csvControl[self::FIELD_DELIMITER] = $delimiter;

                return;
            }
        }

        if (! $this->csvControl[self::FIELD_DELIMITER]) {
            $this->csvControl[self::FIELD_DELIMITER] = self::DELIMITERS[0];
        }
    }
}
