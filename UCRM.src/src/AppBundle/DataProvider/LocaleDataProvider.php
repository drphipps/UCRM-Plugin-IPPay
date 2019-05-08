<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Locale;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\Request;

class LocaleDataProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getAllLocales(): array
    {
        return $this->em->getRepository(Locale::class)->findBy([], ['name' => 'ASC']);
    }

    public function getPreferredLocale(Request $request, ?array $locales = null): string
    {
        $locales = $locales ?? $this->getAllLocales();

        $codes = array_map(
            function (Locale $locale) {
                return $locale->getCode();
            },
            $locales
        );
        usort($codes, function (string $a, string $b) {
            if ($a === Locale::DEFAULT_CODE) {
                return -1;
            }

            if ($b === Locale::DEFAULT_CODE) {
                return 1;
            }

            return 0;
        });

        $locales = [];
        foreach ($codes as $code) {
            $locales[$code] = $code;
            if (Strings::contains($code, '_')) {
                $base = explode('_', $code, 2)[0];
                $locales[$base] = Locale::DEFAULT_BASE_CODES[$base] ?? $code;
            }
        }

        $preferred = $request->getPreferredLanguage(array_keys($locales));

        return $locales[$preferred];
    }

    public function getLocaleById(int $id): ?Locale
    {
        return $this->em->getRepository(Locale::class)->find($id);
    }
}
