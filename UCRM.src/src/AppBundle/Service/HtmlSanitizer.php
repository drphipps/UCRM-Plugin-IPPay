<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

class HtmlSanitizer
{
    /**
     * @var \HTMLPurifier
     */
    private $purifier;

    public function __construct()
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->autoFinalize = false;
        $config->set(
            'URI.AllowedSchemes',
            array_merge(
                $config->get('URI.AllowedSchemes'),
                [
                    'data' => true,
                ]
            )
        );
        $config->autoFinalize = true;

        $this->purifier = new \HTMLPurifier($config);
    }

    public function sanitize(string $html): string
    {
        return $this->purifier->purify($html);
    }
}
