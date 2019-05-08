<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Entity\Import\AbstractImport;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class CsvUploadData
{
    /**
     * @var UploadedFile|null
     *
     * @Assert\File(
     *     maxSize = "50M",
     * )
     */
    public $file;

    /**
     * @var string
     *
     * @Assert\Choice(choices=AbstractImport::DELIMITERS, strict=true)
     */
    public $delimiter = AbstractImport::DEFAULT_DELIMITER;

    /**
     * @var string
     *
     * @Assert\Choice(choices=AbstractImport::ENCLOSURES, strict=true)
     */
    public $enclosure = AbstractImport::DEFAULT_ENCLOSURE;

    /**
     * @var string
     *
     * @Assert\Length(max = 1)
     */
    public $escape = AbstractImport::DEFAULT_ESCAPE;

    /**
     * @var bool
     */
    public $hasHeader = true;

    /**
     * @var bool
     */
    public $autoDetectStructure = true;
}
