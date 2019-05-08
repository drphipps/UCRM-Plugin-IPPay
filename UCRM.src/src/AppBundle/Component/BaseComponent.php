<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component;

use AppBundle\Component\Annotation\Persistent;
use AppBundle\Component\Grid\Grid;

abstract class BaseComponent
{
    public const AJAX_REQUEST_IDENTIFIER = 'ajaxRequestIdentifier';

    /**
     * @var string
     */
    private $name;

    /**
     * @var ComponentFactory
     */
    private $componentFactory;

    /**
     * @Persistent()
     *
     * @var string|null
     */
    protected $do;

    /**
     * @var bool
     */
    protected $isAttached = false;

    /**
     * @var array
     */
    private $persistentParameters;

    /**
     * @var array
     */
    private $defaultProperties;

    /**
     * @var string|null
     */
    private $route;

    final public function __construct(string $name, ComponentFactory $componentFactory)
    {
        $this->name = $name;
        $this->componentFactory = $componentFactory;
    }

    public function setRoute(?string $route): void
    {
        $this->route = $route;
    }

    /**
     * Parses instance attributes from query string.
     */
    public function attached()
    {
        foreach ($this->componentFactory->getComponentUrlQueryParameters($this->getName()) as $propertyName => $value) {
            $this->{$propertyName} = $value;
        }

        $this->processSignal();

        $this->isAttached = true;
    }

    public function generateUrl(
        array $parameters = [],
        bool $includePersistentParameters = true,
        bool $includeAjaxRequestIdentifier = false
    ): string {
        $urlParameters = $this->getUrlParameters(
            $parameters,
            $includePersistentParameters,
            $includeAjaxRequestIdentifier
        );

        return $this->generateUrlDirectly(
            $urlParameters['route'],
            $urlParameters['parameters']
        );
    }

    public function generateUrlDirectly(string $route, array $parameters = []): string
    {
        return $this->componentFactory->generateUrl(
            $route,
            $parameters
        );
    }

    public function redirect(array $parameters): void
    {
        header('Location:' . $this->generateUrl($parameters, true, true), true, 301);
        exit;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * This method returns array of route and parameters used to generate URL with Symfony Router.
     * Persistent parameters, prefixes, etc. are automatically included.
     *
     * To be used directly only when the parameters are needed on their own (e.g. for Shortcuts component),
     * otherwise standard generateUrl should be used.
     */
    protected function getUrlParameters(
        array $parameters = [],
        bool $includePersistentParameters = true,
        bool $includeAjaxRequestIdentifier = false
    ): array {
        if ($includePersistentParameters) {
            if (! $this->persistentParameters) {
                $persistentParameters = [];
                $persistentProperties = $this->componentFactory->getPersistentProperties(get_class($this));
                foreach ($persistentProperties as $persistentProperty => $property) {
                    $persistentParameters[$persistentProperty] = $this->{$persistentProperty};
                }

                $this->persistentParameters = $persistentParameters;
                $this->defaultProperties = (new \ReflectionClass($this))->getDefaultProperties();
            }

            $resultParameters = array_merge($this->persistentParameters, $parameters);

            // Remove default values to have shorter URL
            foreach ($this->defaultProperties as $propertyName => $defaultValue) {
                if (
                    array_key_exists($propertyName, $this->persistentParameters)
                    && $resultParameters[$propertyName] == $defaultValue
                ) {
                    unset($resultParameters[$propertyName]);
                }
            }
        } else {
            $resultParameters = $parameters;
        }

        foreach ($resultParameters as $key => $value) {
            if (strpos($key, Grid::NO_PREFIX) === 0) {
                $resultParameters[str_replace(Grid::NO_PREFIX, '', $key)] = $value;
            } else {
                $resultParameters[$this->componentFactory->componentUrlPrefix($this->getName(), $key)] = $value;
            }
            unset($resultParameters[$key]);
        }

        return $this->componentFactory->getUrlParameters(
            $this->getName(),
            $resultParameters,
            $this->route,
            $includeAjaxRequestIdentifier
        );
    }

    /**
     * Processes a signal given by URL - it catches variable 'do' in URL and tries to call given method 'valueAction'.
     */
    private function processSignal(): void
    {
        if (! is_string($this->do)) {
            return;
        }

        $method = sprintf('%sAction', $this->do);
        if (method_exists($this, $method)) {
            $this->do = null;
            $this->{$method}();
        }
    }
}
