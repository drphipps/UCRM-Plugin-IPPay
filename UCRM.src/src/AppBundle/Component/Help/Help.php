<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Help;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class Help
{
    private const DEFAULT_SECTION = 'index';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(TranslatorInterface $translator, \Twig_Environment $twig)
    {
        $this->translator = $translator;
        $this->twig = $twig;
    }

    public function getTemplatePath(?string $section): ?string
    {
        $section = $section ? str_replace('-', '_', $section) : self::DEFAULT_SECTION;
        if ($this->translator instanceof Translator || method_exists($this->translator, 'getFallbackLocales')) {
            $locales = $this->translator->getFallbackLocales();
        } else {
            $locales = [];
        }
        array_unshift($locales, $this->translator->getLocale());

        foreach ($locales as $locale) {
            $path = sprintf('help/%s/%s.html.twig', $locale, $section);
            if ($this->doesPathExist($path)) {
                return $path;
            }
        }

        if ($section === self::DEFAULT_SECTION) {
            return null;
        }

        return $this->getTemplatePath(self::DEFAULT_SECTION);
    }

    private function doesPathExist(string $path): bool
    {
        try {
            $this->twig->load($path);
        } catch (\Twig_Error_Loader $e) {
            return false;
        }

        return true;
    }
}
