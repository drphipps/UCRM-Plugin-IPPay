<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\CsvImport;

use AppBundle\Entity\CsvImport;
use Symfony\Component\EventDispatcher\Event;

class CsvImportEditEvent extends Event
{
    /**
     * @var CsvImport
     */
    private $csvImport;

    public function __construct(CsvImport $csvImport)
    {
        $this->csvImport = $csvImport;
    }

    public function getCsvImport(): CsvImport
    {
        return $this->csvImport;
    }
}
