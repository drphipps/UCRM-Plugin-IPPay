<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class ComponentFactory
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var array
     */
    private $components = [];

    public function __construct(RouterInterface $router, RequestStack $requestStack, Reader $annotationReader)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->annotationReader = $annotationReader;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return BaseComponent
     *
     * @throws \Exception
     */
    public function createComponent($type, $name, bool $attach = true)
    {
        if (! class_exists($type)) {
            throw new \Exception('Unknown class "%s"', $type);
        }

        if (array_key_exists($name, $this->components)) {
            throw new \InvalidArgumentException(sprintf('Name "%s" is already in use', $name));
        }

        /** @var BaseComponent $component */
        $component = new $type($name, $this);
        if (! is_subclass_of($type, BaseComponent::class)) {
            throw new \InvalidArgumentException(
                sprintf('You are trying to create a component that is not sub class of BaseComponent')
            );
        }

        $this->components[$name] = $component;
        if ($attach) {
            $component->attached();
        }

        return $component;
    }

    public function getUrlParameters(
        $componentName,
        array $resultParameters = [],
        ?string $route,
        bool $includeAjaxRequestIdentifier
    ): array {
        $resultParameters = array_merge($this->fillMissingParameters($componentName), $resultParameters);
        $request = $this->requestStack->getCurrentRequest();

        // Pass through AJAX request identifier. For example in case of AJAX redirect (grid filter clear).
        if ($includeAjaxRequestIdentifier && $identifier = $request->get(BaseComponent::AJAX_REQUEST_IDENTIFIER)) {
            $resultParameters[BaseComponent::AJAX_REQUEST_IDENTIFIER] = $identifier;
        }

        return [
            'route' => $route ?? $request->get('_route'),
            'parameters' => $resultParameters,
        ];
    }

    public function generateUrl(string $route, array $parameters): string
    {
        return $this->router->generate(
            $route,
            $parameters
        );
    }

    /**
     * @param string $name
     * @param string $parameter
     *
     * @return string
     */
    public function componentUrlPrefix($name, $parameter)
    {
        return $name . '-' . $parameter;
    }

    /**
     * This methods tries to find parameters from all component that are in URL address and must persist. Excluding
     * given $componentName.
     *
     * @param string $excludeComponentName Component name that is excluded
     *
     * @return array
     */
    protected function fillMissingParameters($excludeComponentName)
    {
        $result = [];
        foreach ($this->components as $componentName => $component) {
            if ($componentName == $excludeComponentName) {
                continue;
            }

            $componentParametersInQuery = $this->getComponentUrlQueryParameters($componentName);
            if (! $componentParametersInQuery) {
                continue;
            }

            // Remove default values to have shorter URL
            $reflectionClass = new \ReflectionClass($component);
            foreach ($reflectionClass->getDefaultProperties() as $propertyName => $defaultValue) {
                if (false === array_key_exists($propertyName, $componentParametersInQuery)) {
                    continue;
                }

                if ($componentParametersInQuery[$propertyName] != $defaultValue) {
                    $finalParameterKey = $this->componentUrlPrefix($componentName, $propertyName);
                    $result[$finalParameterKey] = $componentParametersInQuery[$propertyName];
                }
            }
        }

        return $result;
    }

    /**
     * @param string $componentName
     *
     * @return array
     */
    public function getComponentUrlQueryParameters($componentName)
    {
        $result = [];
        parse_str($this->requestStack->getCurrentRequest()->getQueryString(), $queryParameters);
        $persistentProperties = $this->getPersistentProperties($this->components[$componentName]);
        foreach ($queryParameters as $parameter => $value) {
            if (0 !== strpos($parameter, $componentName . '-')) {
                continue;
            }

            $propertyName = str_replace($componentName . '-', '', $parameter);
            if (array_key_exists($propertyName, $persistentProperties)) {
                $result[$propertyName] = $value;
            }
        }

        return $result;
    }

    /**
     * @param string $class
     *
     * @return array
     */
    public function getPersistentProperties($class)
    {
        $result = [];
        $reflectionClass = new \ReflectionClass($class);
        foreach ($reflectionClass->getProperties() as $property) {
            $persistent = $this->annotationReader->getPropertyAnnotation($property, Annotation\Persistent::class);
            if ($persistent) {
                if ($property->isPrivate()) {
                    throw new \LogicException(
                        sprintf('Persistent property "%s" cannot be private', $property->getName())
                    );
                }

                $result[$property->getName()] = $property;
            }
        }

        return $result;
    }
}
