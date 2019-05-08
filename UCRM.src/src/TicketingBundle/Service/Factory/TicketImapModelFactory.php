<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Factory;

use AppBundle\Service\Encryption;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Ddeboer\Imap\Server;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Nette\Utils\Strings;
use TicketingBundle\Entity\TicketImapInbox;
use TicketingBundle\Service\TicketImapModel;

class TicketImapModelFactory
{
    /**
     * @var Encryption
     */
    private $encryption;

    public function __construct(Encryption $encryption)
    {
        $this->encryption = $encryption;
    }

    /**
     * @throws AuthenticationFailedException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function create(TicketImapInbox $ticketImapInbox): TicketImapModel
    {
        $parameters = [];
        if ($this->fudgeMsOff($ticketImapInbox->getUsername())) {
            // see https://stackoverflow.com/questions/28481028/access-office356-shared-mailbox-with-php
            $parameters['DISABLE_AUTHENTICATOR'] = 'PLAIN';
        }

        $imapServer = new Server(
            $ticketImapInbox->getServerName(),
            (string) $ticketImapInbox->getServerPort(),
            $ticketImapInbox->isVerifySslCertificate() ? '/imap/ssl/validate-cert' : '/imap/ssl/novalidate-cert',
            $parameters
        );

        return new TicketImapModel(
            $imapServer->authenticate(
                ($ticketImapInbox->getUsername() ?: $ticketImapInbox->getEmailAddress()) ?? '',
                $this->encryption->decrypt($ticketImapInbox->getPassword() ?? '')
            )
        );
    }

    /**
     * @return bool: true if likely a MS Office365 shared mailbox,
     *               i.e. of the format somename@example.com/sharedmailboxname
     */
    private function fudgeMsOff($username): bool
    {
        return (bool) Strings::match($username, '~^(?>[^@]+@)[^/]+/~');
    }
}
