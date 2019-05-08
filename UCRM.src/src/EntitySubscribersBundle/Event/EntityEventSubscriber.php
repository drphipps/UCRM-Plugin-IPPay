<?php

namespace EntitySubscribersBundle\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

interface EntityEventSubscriber extends EventSubscriber
{
    public function subscribesToEntity(LoadClassMetadataEventArgs $event): bool;
}
