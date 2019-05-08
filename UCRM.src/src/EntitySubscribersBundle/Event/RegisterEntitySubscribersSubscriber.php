<?php

namespace EntitySubscribersBundle\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RegisterEntitySubscribersSubscriber implements EventSubscriber
{
    /**
     * @var array
     */
    private $subscribers;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(array $subscribers, ContainerInterface $container)
    {
        $this->subscribers = $subscribers;
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $event->getClassMetadata();

        foreach ($this->subscribers as $service) {
            /** @var EntityEventSubscriber $subscriber */
            $subscriber = $this->container->get($service);

            if (! $subscriber->subscribesToEntity($event)) {
                continue;
            }

            foreach ($subscriber->getSubscribedEvents() as $eventName => $method) {
                if (is_numeric($eventName)) {
                    $eventName = $method;
                }

                $metadata->addEntityListener($eventName, get_class($subscriber), $method);
            }
        }
    }
}
