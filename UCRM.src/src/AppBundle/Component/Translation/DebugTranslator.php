<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Translation;

use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Component\Translation\TranslatorInterface;

class DebugTranslator extends DataCollectorTranslator
{
    /**
     * @var string
     */
    private $environment;

    public function __construct(TranslatorInterface $translator, string $environment)
    {
        parent::__construct($translator);

        $this->environment = $environment;
    }

    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->wrapMissingMessage(
            parent::trans($id, $parameters, $domain, $locale),
            $id,
            $domain,
            $locale
        );
    }

    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->wrapMissingMessage(
            parent::transChoice($id, $number, $parameters, $domain, $locale),
            $id,
            $domain,
            $locale
        );
    }

    private function wrapMissingMessage($translated, $id, $domain, $locale)
    {
        if (PHP_SAPI === 'cli' || $this->environment !== 'dev') {
            return $translated;
        }

        $domain = $domain ?? 'messages';
        $catalogue = $this->getCatalogue($locale);
        if ($catalogue->defines($id, $domain) || $catalogue->has($id, $domain)) {
            return $translated;
        }

        return '😱 ' . $translated . ' ⛔️';
    }
}
