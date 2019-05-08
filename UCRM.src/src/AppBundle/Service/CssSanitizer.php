<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use HTMLPurifier_HTMLDefinition;

class CssSanitizer
{
    /**
     * @var \HTMLPurifier
     */
    private $purifier;

    public function __construct()
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Filter.ExtractStyleBlocks', true);
        $config->set('Filter.ExtractStyleBlocks.Escaping', false);
        $config->set('CSS.Trusted', true);
        $config->set('CSS.AllowTricky', true);
        $config->set('CSS.AllowImportant', true);
        $config->set('CSS.AllowDuplicates', true);
        $config->set('CSS.Proprietary', true);
        $config->set('HTML.DefinitionID', 'UCRM');
        $config->set('HTML.DefinitionRev', 1);
        $config->set('Cache.DefinitionImpl', null);
        /** @var HTMLPurifier_HTMLDefinition|null $def */
        $def = $config->maybeGetRawHTMLDefinition();
        if ($def) {
            $def->addElement('body', 'Block', 'Flow', 'Common');
        }
        $this->purifier = new \HTMLPurifier($config);
    }

    public function sanitize(string $css): string
    {
        $this->purifier->purify(
            sprintf(
                '<style>%s</style>',
                strip_tags($css)
            )
        );

        return $this->purifier->context->get('StyleBlocks')[0];
    }
}
