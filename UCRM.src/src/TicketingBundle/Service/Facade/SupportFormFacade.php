<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Facade;

use AppBundle\Entity\Client;
use AppBundle\Entity\Option;
use AppBundle\Exception\OptionNotFoundException;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\ExceptionStash;
use AppBundle\Service\Options;
use AppBundle\Util\Message;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TicketingBundle\Handler\TicketImapImportHandler;

class SupportFormFacade
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ExceptionStash
     */
    private $exceptionStash;

    /**
     * @var EmailEnqueuer
     */
    private $emailEnqueuer;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        Options $options,
        RouterInterface $router,
        ExceptionStash $exceptionStash,
        EmailEnqueuer $emailEnqueuer,
        \Twig_Environment $twig,
        TranslatorInterface $translator
    ) {
        $this->options = $options;
        $this->router = $router;
        $this->exceptionStash = $exceptionStash;
        $this->emailEnqueuer = $emailEnqueuer;
        $this->twig = $twig;
        $this->translator = $translator;
    }

    public function handleSupportForm(Client $client, string $subject, string $body): void
    {
        try {
            $message = $this->createMessage($client, $subject, $body);
        } catch (PublicUrlGeneratorException | OptionNotFoundException $exception) {
            $this->exceptionStash->add($exception);

            return;
        }

        $this->emailEnqueuer->enqueue(
            $message,
            EmailEnqueuer::PRIORITY_HIGH
        );
    }

    /**
     * @throws PublicUrlGeneratorException
     * @throws OptionNotFoundException
     */
    public function createMessage(Client $client, string $subject, string $body): Message
    {
        $supportEmail = $this->options->get(Option::SUPPORT_EMAIL_ADDRESS);
        $supportName = null;
        if (! $supportEmail && $client) {
            $supportEmail = $client->getOrganization()->getEmail();
            $supportName = $client->getOrganization()->getName();
        }

        if (! $supportEmail) {
            // rel="noopener noreferrer" added in EN translation, kept original here for other translations to work
            // the link is internal, so there is no security concern
            $ex = new OptionNotFoundException(
                'Email not sent. Support email address is not set! <a href="%link%" target="_blank">Set it here.</a>'
            );
            $ex->setParameters(
                ['%link%' => $this->router->generate('setting_mailer_edit') . '#setting-addresses-form']
            );
            throw $ex;
        }

        $siteName = $this->options->get(Option::SITE_NAME);
        $emailSubject = $this->translator->trans(
            'New message from Client Zone - %clientName%',
            [
                '%clientName%' => $this->getClientName($client),
            ]
        );
        $emailSubject = $siteName ? sprintf('[%s] %s', $siteName, $emailSubject) : $emailSubject;

        $message = new Message();
        $message->setClient($client);
        $message->setSubject($emailSubject);
        $message->setTo($supportEmail);
        $message->setFrom($supportEmail, $supportName);
        $message->setSender(
            $this->options->get(
                Option::MAILER_SENDER_ADDRESS,
                $client && $client->getOrganization() ? $client->getOrganization()->getEmail() : null
            )
        );
        $headers = $message->getHeaders();
        $headers->addTextHeader('X-Mailer', TicketImapImportHandler::X_MAILER_HEADER);

        $message->setBody(
            $this->twig->render(
                '@Ticketing/client/support_form_email.html.twig',
                [
                    'client' => $client,
                    'subject' => $subject,
                    'message' => $body,
                ]
            ),
            'text/html'
        );

        if ($client && $contactEmails = $client->getContactEmails()) {
            $message->addReplyTo(reset($contactEmails), $client->getNameForView());
        }

        return $message;
    }

    private function getClientName(Client $client): string
    {
        $clientIdType = $this->options->get(Option::CLIENT_ID_TYPE);

        return sprintf(
            '%s (%s: %s)',
            $client->getNameForView(),
            $clientIdType === Option::CLIENT_ID_TYPE_DEFAULT
                ? $this->translator->trans('ID')
                : $this->translator->trans('Custom ID'),
            $clientIdType === Option::CLIENT_ID_TYPE_DEFAULT
                ? $client->getId()
                : $client->getUserIdent()
        );
    }
}
