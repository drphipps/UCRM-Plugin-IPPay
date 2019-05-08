<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Document;

use AppBundle\Entity\Document;

final class DocumentDeleteEvent extends AbstractDocumentEvent
{
    /**
     * @var int
     */
    private $id;

    public function __construct(Document $document, int $id)
    {
        parent::__construct($document);
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
