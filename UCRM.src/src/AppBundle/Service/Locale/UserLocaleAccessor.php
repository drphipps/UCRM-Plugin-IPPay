<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Service\Locale;

use AppBundle\Entity\Locale;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserLocaleAccessor
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Locale|null
     */
    private $locale;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em)
    {
        $this->translator = $translator;
        $this->em = $em;
    }

    public function getLocale(): Locale
    {
        if (! $this->locale) {
            $this->locale = $this->em->getRepository(Locale::class)->findOneBy(
                [
                    'code' => $this->translator->getLocale(),
                ]
            );

            if (! $this->locale) {
                throw new \RuntimeException(
                    sprintf('Locale entity not found for locale "%s".', $this->translator->getLocale())
                );
            }
        }

        return $this->locale;
    }
}
