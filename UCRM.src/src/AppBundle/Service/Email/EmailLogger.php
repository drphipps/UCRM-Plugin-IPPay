<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Email;

use AppBundle\Entity\EmailLog;
use AppBundle\Entity\General;
use AppBundle\Service\EntityManagerRecreator;
use AppBundle\Service\Options;
use AppBundle\Util\Helpers;
use AppBundle\Util\Message;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Swift_FileStream;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Translation\TranslatorInterface;

class EmailLogger
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var EntityManagerRecreator
     */
    private $emRecreator;

    /**
     * @var string
     */
    private $rootDir;

    public function __construct(
        EntityManager $em,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        Options $options,
        EntityManagerRecreator $emRecreator,
        string $rootDir
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->options = $options;
        $this->emRecreator = $emRecreator;
        $this->rootDir = $rootDir;
    }

    public function log(
        \Swift_Message $message,
        ?string $logMessage = null,
        ?int $logStatus = null,
        array $transParameters = []
    ): EmailLog {
        if (! $this->em->isOpen()) {
            $this->em = $this->emRecreator->create($this->em);
        }

        $log = new EmailLog();
        $log->setSubject($message->getSubject());
        $log->setAddressFrom(Json::encode($message->getFrom()) ?? '');
        $log->setSender(Json::encode($message->getSender()) ?? '');
        $log->setRecipient(implode(', ', array_keys($message->getTo())));
        $log->setCreatedDate(new \DateTime());
        $log->setBody($this->getHtmlMessageBody($message) ?? $message->getBody());
        if ($message instanceof Message) {
            $log->setClient($message->getClient() ? $this->em->merge($message->getClient()) : null);
            $log->setInvoice($message->getInvoice() ? $this->em->merge($message->getInvoice()) : null);
            $log->setQuote($message->getQuote() ? $this->em->merge($message->getQuote()) : null);
            $log->setBulkMail($message->getMailing());
        }
        $log->setAttachments($this->setAttachments($message));
        $log->setMessageId(Strings::truncate(Helpers::getMessageId($message), 32, ''));
        $log->setMessage($logMessage ? $this->translator->trans($logMessage, $transParameters) : null);
        $log->setStatus($logStatus);
        $log->setSentInSandbox((bool) $this->options->getGeneral(General::SANDBOX_MODE, false));

        $token = $this->tokenStorage->getToken();
        if ($token instanceof UsernamePasswordToken) {
            $log->setUser($this->em->merge($token->getUser()));
        }

        $this->em->persist($log);
        $this->em->flush($log);

        return $log;
    }

    public function activateLog(
        string $messageId,
        \Swift_Message $message,
        int $successfulRecipients,
        ?\Exception $exception,
        ?array $failedRecipients
    ): void {
        if (! $this->em->isOpen()) {
            $this->em = $this->emRecreator->create($this->em);
        }

        $log = $this->em->getRepository(EmailLog::class)->findOneBy(
            [
                'messageId' => $messageId,
            ]
        );

        if (! $log) {
            return;
        }

        if ($failedRecipients) {
            $log->setFailedRecipients(implode(', ', $failedRecipients));
        }

        if ($exception) {
            $log->setStatus(EmailLog::STATUS_ERROR);
            $log->setMessage($this->translator->trans('Sending of email failed.') . ' ' . $exception->getMessage());
        } elseif ($successfulRecipients === 0) {
            $log->setStatus(EmailLog::STATUS_ERROR);
            $log->setMessage($this->translator->trans('Sending of email failed. No recipient accepted for delivery.'));
        } else {
            $log->setStatus(EmailLog::STATUS_OK);
            $log->setMessage($this->translator->trans('Email has been sent successfully'));
            $log->setMessageId(null);
        }

        $log->setCreatedDate(new \DateTime());

        $recipient = implode(', ', array_keys($message->getTo()));
        if ($recipient !== $log->getRecipient()) {
            $log->setOriginalRecipient($log->getRecipient());
            $log->setRecipient($recipient);
        }

        $this->em->flush($log);
    }

    private function setAttachments(\Swift_Message $message): ?string
    {
        $attachments = [];
        foreach ($message->getChildren() as $attachment) {
            if (! $attachment instanceof \Swift_Attachment) {
                continue;
            }

            $path = $this->getAttachmentPath($attachment);
            if (! $path) {
                continue;
            }

            $attachments[] = $path;
        }

        return $attachments ? implode(', ', $attachments) : null;
    }

    /**
     * This method walks gets path to attachment file with stripped %kernel.root_dir%
     * Only works if the attachment has Swift_FileStream body.
     *
     * As the property is not accessible with methods and is private under Swift_Mime_SimpleMimeEntity,
     * reflection must be used to get it.
     */
    private function getAttachmentPath(\Swift_Attachment $attachment): ?string
    {
        $reflection = new \ReflectionObject($attachment);
        while ($reflection && ! $reflection->hasProperty('_body')) {
            $reflection = $reflection->getParentClass();
        }

        if (! $reflection || ! $reflection->hasProperty('_body')) {
            return null;
        }

        $body = $reflection->getProperty('_body');
        $body->setAccessible(true);
        $body = $body->getValue($attachment);

        if (! $body instanceof Swift_FileStream) {
            return null;
        }

        $path = Strings::after($body->getPath(), $this->rootDir);

        return $path === false ? null : $path;
    }

    private function getHtmlMessageBody(\Swift_Message $message): ?string
    {
        if ($message->getContentType() === 'text/html') {
            return $message->getBody();
        }

        $messageBody = null;
        foreach ($message->getChildren() as $child) {
            if ($child->getContentType() === 'text/html') {
                $messageBody = $child->getBody();
            }
        }

        return $messageBody;
    }
}
