<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\QueryFactory;

use Nette\Utils\Strings;
use Symfony\Component\Translation\TranslatorInterface;

abstract class BaseLocaleQueryFactory extends BaseQueryFactory
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    protected function getAnalyzerFromLocale(): string
    {
        switch (Strings::lower($this->translator->getLocale())) {
            case 'ca_es':
                return 'catalan';
            case 'cs':
                return 'czech';
            case 'da':
                return 'danish';
            case 'de':
                return 'german';
            case 'en':
            case 'en_us':
            case 'en_ca':
                return 'english';
            case 'es':
                return 'spanish';
            case 'fr':
                return 'french';
            case 'hu':
                return 'hungarian';
            case 'it':
                return 'italian';
            case 'lv':
                return 'latvian';
            case 'nl':
                return 'dutch';
            case 'pt':
                return 'portuguese';
            case 'pt_br':
                return 'brazilian';
            case 'sk':
                return 'custom_standard_analyzer';
            case 'sv':
                return 'swedish';
            case 'tr':
                return 'turkish';
            case 'ru':
                return 'russian';
            case 'bg':
                return 'bulgarian';
            default:
                return 'keyword_analyzer';
        }
    }
}
