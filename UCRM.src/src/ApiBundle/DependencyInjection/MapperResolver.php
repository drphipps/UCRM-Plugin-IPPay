<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\DependencyInjection;

use ApiBundle\Mapper\AbstractMapper;
use Nette\Utils\Strings;
use Psr\Container\ContainerInterface;

class MapperResolver
{
    /**
     * @var string[]
     */
    private $services;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(array $services, ContainerInterface $container)
    {
        $this->container = $container;
        $this->services = $services;
    }

    public function get(string $name): ?AbstractMapper
    {
        $name = Strings::lower($name);

        return array_key_exists($name, $this->services) ? $this->container->get($this->services[$name]) : null;
    }
}
