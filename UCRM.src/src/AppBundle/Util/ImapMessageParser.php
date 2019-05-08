<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use Ddeboer\Imap\Exception\UnexpectedEncodingException;
use Ddeboer\Imap\Exception\UnsupportedCharsetException;
use Ddeboer\Imap\Message\PartInterface;
use Nette\Utils\Strings;

/**
 * This class re-implements getBodyHtml and getBodyText methods from Ddeboer\Imap\Message\AbstractMessage
 * to handle UnexpectedEncodingException nicely.
 * We don't really care if it can't be decoded, we just return plain content in that case.
 */
class ImapMessageParser
{
    public static function getBodyHtml(PartInterface $message): string
    {
        $body = '';
        $iterator = new \RecursiveIteratorIterator($message, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $part) {
            if (PartInterface::SUBTYPE_HTML === $part->getSubtype()) {
                try {
                    $body = $part->getDecodedContent();
                } catch (UnexpectedEncodingException | UnsupportedCharsetException $exception) {
                    $body = $part->getContent();
                }

                break;
            }
        }

        // If message has no parts and is HTML, return content of message itself.
        if (! $body && PartInterface::SUBTYPE_HTML === $message->getSubtype()) {
            try {
                $body = $message->getDecodedContent();
            } catch (UnexpectedEncodingException | UnsupportedCharsetException $exception) {
                $body = $message->getContent();
            }
        }

        return Strings::fixEncoding($body);
    }

    public static function getBodyText(PartInterface $message): string
    {
        $body = '';
        $iterator = new \RecursiveIteratorIterator($message, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $part) {
            if (PartInterface::SUBTYPE_PLAIN === $part->getSubtype()) {
                try {
                    $body = $part->getDecodedContent();
                } catch (UnexpectedEncodingException | UnsupportedCharsetException $exception) {
                    $body = $part->getContent();
                }

                break;
            }
        }

        // If message has no parts, return content of message itself.
        if (! $body && PartInterface::SUBTYPE_PLAIN === $message->getSubtype()) {
            try {
                $body = $message->getDecodedContent();
            } catch (UnexpectedEncodingException | UnsupportedCharsetException $exception) {
                $body = $message->getContent();
            }
        }

        return Strings::fixEncoding($body);
    }
}
