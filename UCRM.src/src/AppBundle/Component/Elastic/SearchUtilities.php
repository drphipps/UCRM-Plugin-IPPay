<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic;

use AppBundle\Entity\Client;
use Symfony\Component\Translation\TranslatorInterface;

class SearchUtilities
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    public function getClientDescription(Client $client): string
    {
        $description = [
            sprintf(
                '%s: %s',
                $this->translator->trans('ID'),
                $client->getId()
            ),
            implode(', ', $client->getEmails()),
            implode(', ', $client->getPhones()),
            $client->getAddressString(),
        ];

        return implode(' / ', array_filter($description));
    }
}
