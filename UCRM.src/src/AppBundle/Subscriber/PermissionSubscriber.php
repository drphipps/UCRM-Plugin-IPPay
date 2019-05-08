<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber;

use AppBundle\Controller\PermissionCheckedInterface;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Security\PermissionRequiredChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PermissionSubscriber implements EventSubscriberInterface
{
    /**
     * @var PermissionRequiredChecker
     */
    private $permissionRequiredChecker;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    public function __construct(
        PermissionRequiredChecker $permissionRequiredChecker,
        PermissionGrantedChecker $permissionGrantedChecker
    ) {
        $this->permissionRequiredChecker = $permissionRequiredChecker;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'handleKernelController',
        ];
    }

    /**
     * @throws AccessDeniedException when user has insufficient permissions
     */
    public function handleKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (! is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof PermissionCheckedInterface) {
            $this->permissionRequiredChecker
                ->getRequiredPermissionForController($controller, $outPermission, $outSubject);

            if ($outPermission !== null) {
                $this->permissionGrantedChecker
                    ->denyAccessUnlessGranted($outPermission, $outSubject);
            }
        }
    }
}
