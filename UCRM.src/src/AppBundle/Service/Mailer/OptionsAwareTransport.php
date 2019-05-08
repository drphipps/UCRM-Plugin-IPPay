<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Service\Mailer;

use AppBundle\Entity\Option;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Service\Options;
use AppBundle\Util\Helpers;
use Swift_Events_EventListener;

class OptionsAwareTransport implements \Swift_Transport
{
    /**
     * @var \Swift_Transport_EsmtpTransport
     */
    private $transport;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var EmailLogger
     */
    private $logger;

    /**
     * @var array
     */
    private $lastConfiguration;

    public function __construct(
        \Swift_Transport_EsmtpTransport $transport,
        Options $options,
        EmailLogger $logger
    ) {
        $this->transport = $transport;
        $this->options = $options;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->transport->isStarted();
    }

    /**
     * Start by this method is disabled and moved to send method in order to provide
     * error logging in context of a message.
     * If you still need to explicitly start transport, use realStart method instead.
     */
    public function start()
    {
    }

    public function realStart()
    {
        $this->configure();

        $this->transport->start();
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->transport->stop();
    }

    /**
     * {@inheritdoc}
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        return $this->transport->registerPlugin($plugin);
    }

    /**
     * {@inheritdoc}
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->configure();

        $failedRecipients = (array) $failedRecipients;
        $exception = null;
        // We must get message ID beforehand to pair with log, because $this->transport->send() changes it.
        $messageId = Helpers::getMessageId($message);
        $return = 0;

        try {
            if (! $this->isStarted()) {
                $this->transport->start();
            }

            $return = $this->transport->send($message, $failedRecipients);
        } catch (\Exception $exception) {
            if ($exception instanceof \Swift_RfcComplianceException) {
                foreach ($message->getTo() as $address => $name) {
                    $failedRecipients[] = $address;
                }
            }
        }

        if ($message instanceof \Swift_Message) {
            $this->logger->activateLog($messageId, $message, $return, $exception, $failedRecipients);
        }

        // If PDF does not exist ignore the error and go to next mail immediately.
        // This works only because the exception is not caught and transformed to Swift_TransportException
        // in Swift_Transport_AbstractSmtpTransport::_streamMessage().
        // If Swift changes this behavior later we will need to check previous exception instead.
        if ($exception && get_class($exception) !== \Swift_IoException::class) {
            throw $exception;
        }

        return $return;
    }

    /**
     * Reloads the transport configuration and restarts the transport if it changed.
     */
    private function configure()
    {
        $configuration = $this->getNewConfiguration();

        if ($configuration === $this->lastConfiguration) {
            return;
        }

        $this->lastConfiguration = $configuration;

        $authHandler = null;
        foreach ($this->transport->getExtensionHandlers() as $handler) {
            if ($handler instanceof \Swift_Transport_Esmtp_AuthHandler) {
                $authHandler = $handler;
                break;
            }
        }

        if ($authHandler instanceof \Swift_Transport_Esmtp_AuthHandler) {
            $authHandler->setUsername($configuration['username']);
            $authHandler->setPassword($configuration['password']);
            $authHandler->setAuthMode($configuration['authMode']);
        }

        $this->transport->setEncryption($configuration['encryption']);
        $this->transport->setHost($configuration['host']);
        $this->transport->setPort($configuration['port']);
        if ($this->transport->getLocalDomain() === 'localhost') {
            $this->transport->setLocalDomain($this->options->get(Option::SERVER_FQDN) ?? gethostname());
        }

        if (! $this->options->get(Option::MAILER_VERIFY_SSL_CERTIFICATES)) {
            $this->transport->setStreamOptions(
                [
                    'ssl' => [
                        'allow_self_signed' => true,
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]
            );
        }

        if ($this->isStarted()) {
            $this->stop();
        }
    }

    /**
     * @return array
     */
    private function getNewConfiguration()
    {
        if ($this->options->get(Option::MAILER_TRANSPORT) === Option::MAILER_TRANSPORT_GMAIL) {
            $configuration = [
                'encryption' => 'ssl',
                'authMode' => 'login',
                'host' => 'smtp.gmail.com',
                'port' => 465,
            ];
        } else {
            $encryption = $this->options->get(Option::MAILER_ENCRYPTION);

            $configuration = [
                'encryption' => $encryption,
                'authMode' => $this->options->get(Option::MAILER_AUTH_MODE),
                'host' => $this->options->get(Option::MAILER_HOST),
                'port' => $this->options->get(Option::MAILER_PORT, false) ?: ('ssl' === $encryption ? 465 : 25),
            ];
        }

        $configuration['username'] = $this->options->get(Option::MAILER_USERNAME);
        $configuration['password'] = $this->options->get(Option::MAILER_PASSWORD);

        return $configuration;
    }
}
