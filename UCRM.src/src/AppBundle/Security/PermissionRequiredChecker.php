<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Security;

use AppBundle\Entity\UserGroup;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Routing\RouterInterface;

class PermissionRequiredChecker
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(RouterInterface $router, Reader $annotationReader)
    {
        $this->router = $router;
        $this->annotationReader = $annotationReader;
    }

    public function getRequiredPermissionForController(
        array $controller,
        ?string &$outPermission,
        ?string &$outSubject
    ): void {
        if (! is_callable($controller)) {
            throw new \InvalidArgumentException();
        }
        $reflectionMethod = new \ReflectionMethod($controller[0], $controller[1]);
        $this->getRequiredPermissionForMethod($reflectionMethod, $outPermission, $outSubject);
    }

    /**
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     */
    private function getRequiredPermissionForMethod(
        \ReflectionMethod $reflectionMethod,
        ?string &$outPermission,
        ?string &$outSubject
    ): void {
        $outSubject = $this->findPermissionControllerName($reflectionMethod);
        $outPermission = $this->findPermissionByAnnotation($reflectionMethod);

        if ($outPermission === null) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Method %s::%s doesn\'t have a permission level specified',
                    $reflectionMethod->getDeclaringClass()->getName(),
                    $reflectionMethod->getName()
                )
            );
        }

        if (
            in_array($outPermission, Permission::MODULE_PERMISSIONS, true)
            && ! in_array($outSubject, UserGroup::PERMISSION_MODULES, true)
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Method %s::%s has higher permission but class %s is not betweeen allowed controllers',
                    $outSubject,
                    $reflectionMethod->getName(),
                    $outSubject
                )
            );
        }
    }

    private function findPermissionByAnnotation(\ReflectionMethod $reflectionMethod): ?string
    {
        $annotations = $this->annotationReader->getMethodAnnotations($reflectionMethod);
        $permission = null;

        foreach ($annotations as $annotation) {
            if ($annotation instanceof Permission) {
                $permission = $annotation->getPermission();
                break;
            }
        }

        return $permission;
    }

    private function findPermissionControllerName(\ReflectionMethod $reflectionMethod): ?string
    {
        $outSubject = $reflectionMethod->getDeclaringClass()->getName();

        /** @var PermissionControllerName|null $inheritAnnotation */
        $inheritAnnotation = $this->annotationReader->getClassAnnotation(
            new \ReflectionClass($reflectionMethod->getDeclaringClass()->getName()),
            PermissionControllerName::class
        );

        if ($inheritAnnotation) {
            if ($inheritAnnotation->getController() === $reflectionMethod->getDeclaringClass()->getName()) {
                throw new \Exception(
                    sprintf(
                        'PermissionControllerName class cannot be the same as the controller itself (%s).',
                        $reflectionMethod->getDeclaringClass()->getName()
                    )
                );
            }

            $outSubject = $inheritAnnotation->getController();
        }

        return $outSubject;
    }
}
