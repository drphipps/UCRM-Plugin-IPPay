<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;

class ClientContactRepository extends BaseRepository
{
    /**
     * This method is used to match IMAP imported ticket to a client.
     * If there are matching contacts over multiple clients, we don't want to match the client automatically.
     */
    public function findExactlyOneClientByContactEmail(string $contactEmail): ?Client
    {
        /** @var ClientContact[] $contacts */
        $contacts = $this->findBy(
            [
                'email' => $contactEmail,
            ]
        );

        $clients = [];
        foreach ($contacts as $contact) {
            $clients[$contact->getClient()->getId()] = $contact->getClient();
        }

        return count($clients) === 1
            ? reset($clients)
            : null;
    }
}
