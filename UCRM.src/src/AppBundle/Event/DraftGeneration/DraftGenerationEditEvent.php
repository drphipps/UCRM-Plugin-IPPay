<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\DraftGeneration;

use AppBundle\Entity\DraftGeneration;
use Symfony\Component\EventDispatcher\Event;

class DraftGenerationEditEvent extends Event
{
    /**
     * @var DraftGeneration
     */
    private $draftGeneration;

    public function __construct(DraftGeneration $draftGeneration)
    {
        $this->draftGeneration = $draftGeneration;
    }

    public function getDraftGeneration(): DraftGeneration
    {
        return $this->draftGeneration;
    }
}
