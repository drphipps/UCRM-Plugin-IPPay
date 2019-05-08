<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Translation\Loader;

use Symfony\Component\Translation\Loader\YamlFileLoader as BaseYamlFileLoader;
use Symfony\Component\Translation\MessageCatalogue;

class YamlFileLoader extends BaseYamlFileLoader
{
    private const USED_DOMAINS = [
        'validators',
        'invoice_pdf',
        'account_statement_pdf',
        'client_zone_general',
        'formatting',
        'service_stop_reason',
        'onboarding',
    ];

    private const DEFAULT_DOMAIN = 'messages';

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages'): MessageCatalogue
    {
        // ignoring $domain var (except validators domain) and pass 'messages' instead

        return parent::load($resource, $locale, in_array($domain, self::USED_DOMAINS) ? $domain : self::DEFAULT_DOMAIN);
    }
}
