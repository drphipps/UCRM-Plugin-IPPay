<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Document;

use AppBundle\Entity\Document;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractDocumentEvent extends Event
{
    /**
     * @var Document
     */
    protected $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }
}
