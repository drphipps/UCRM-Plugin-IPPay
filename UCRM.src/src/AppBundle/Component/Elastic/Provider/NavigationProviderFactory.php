<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\Provider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class NavigationProviderFactory
{
    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var FormRegistryInterface
     */
    private $formRegistry;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        array $pathConfiguration,
        FormRegistryInterface $formRegistry,
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator
    ) {
        foreach ($pathConfiguration as $item) {
            $this->paths[$item['path']] = $item['namespacePrefix'];
        }
        $this->formRegistry = $formRegistry;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    public function create(string $locale): NavigationProvider
    {
        return new NavigationProvider(
            $this->paths,
            $this->formRegistry,
            $this->formFactory,
            $this->translator,
            $locale
        );
    }
}
