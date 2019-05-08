<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Locale;

use AppBundle\Entity\Locale;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;

class AppLocaleAccessor
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Locale|null
     */
    private $locale;

    public function __construct(EntityManagerInterface $em, Options $options)
    {
        $this->em = $em;
        $this->options = $options;
    }

    public function getLocale(): Locale
    {
        if (! $this->locale) {
            $this->locale = $this->em->getRepository(Locale::class)->findOneBy(
                [
                    'code' => $this->options->get(Option::APP_LOCALE),
                ]
            );
        }

        // If saved locale does not exist, use english as fallback instead of crashing whole UCRM.
        if (! $this->locale) {
            $this->locale = $this->em->getRepository(Locale::class)->findOneBy(
                [
                    'code' => Locale::DEFAULT_CODE,
                ]
            );
        }

        return $this->locale;
    }
}
