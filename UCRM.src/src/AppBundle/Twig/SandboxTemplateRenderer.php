<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Twig;

use AppBundle\Service\Locale\AppLocaleAccessor;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\TranslatorInterface;

class SandboxTemplateRenderer
{
    /**
     * @var \Twig_Extension_Sandbox
     */
    private $sandboxExtension;

    /**
     * @var \Twig_Environment
     */
    private $safeTwigEnvironment;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var AppLocaleAccessor
     */
    private $appLocaleAccessor;

    public function __construct(
        \Twig_Extension_Sandbox $sandboxExtension,
        TranslationExtension $translationExtension,
        TranslatorInterface $translator,
        AppLocaleAccessor $appLocaleAccessor
    ) {
        $this->sandboxExtension = $sandboxExtension;
        $this->translator = $translator;
        $this->appLocaleAccessor = $appLocaleAccessor;

        $this->safeTwigEnvironment = new \Twig_Environment(
            new \Twig_Loader_Array([])
        );
        $this->safeTwigEnvironment->addExtension($translationExtension);
        $this->safeTwigEnvironment->addExtension($this->sandboxExtension);
    }

    public function render(string $source, array $parameters): string
    {
        $this->sandboxExtension->enableSandbox();
        $translatorLocale = $this->translator->getLocale();
        $this->translator->setLocale($this->appLocaleAccessor->getLocale()->getCode());
        try {
            $html = $this->getTemplateWrapper($source)->render($parameters);
        } finally {
            $this->sandboxExtension->disableSandbox();
            $this->translator->setLocale($translatorLocale);
        }

        return $html;
    }

    private function getTemplateWrapper(string $source): \Twig_TemplateWrapper
    {
        return $this->safeTwigEnvironment->load($this->safeTwigEnvironment->createTemplate($source));
    }
}
