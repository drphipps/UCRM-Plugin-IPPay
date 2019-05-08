<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Import;

use AppBundle\Entity\Import\ImportInterface;
use Symfony\Component\EventDispatcher\Event;

class ImportEditEvent extends Event
{
    /**
     * @var ImportInterface
     */
    private $import;

    /**
     * @var ImportInterface
     */
    private $importBeforeUpdate;

    public function __construct(ImportInterface $import, ImportInterface $importBeforeUpdate)
    {
        $this->import = $import;
        $this->importBeforeUpdate = $importBeforeUpdate;
    }

    public function getImport(): ImportInterface
    {
        return $this->import;
    }

    public function getImportBeforeUpdate(): ImportInterface
    {
        return $this->importBeforeUpdate;
    }
}
