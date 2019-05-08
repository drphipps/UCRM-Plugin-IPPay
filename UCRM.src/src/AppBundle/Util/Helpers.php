<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use DOMElement;
use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class Helpers
{
    public static function typeCastNullable(string $type, $value)
    {
        if ($value === null) {
            return $value;
        }

        return self::typeCast($type, $value);
    }

    public static function typeCastAll(string $type, array $value): array
    {
        return array_map(
            function ($item) use ($type) {
                return self::typeCast($type, $item);
            },
            $value
        );
    }

    public static function typeCast(string $type, $value)
    {
        $result = settype($value, $type);

        if (! $result) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot cast a variable of type "%s" to "%s".',
                    gettype($value),
                    $type
                )
            );
        }

        return $value;
    }

    /**
     * Checks if UCRM is running in demo mode.
     * As console commands don't have server arguments, there is a fallback to demo file check.
     */
    public static function isDemo(): bool
    {
        return (isset($_SERVER['UCRM_DEMO']) && $_SERVER['UCRM_DEMO'] === '1')
            || file_exists('/usr/src/ucrm/UCRM_DEMO');
    }

    public static function forceHttps(): bool
    {
        if (self::isDemo()) {
            return true;
        }

        return (bool) getenv('FORCE_HTTPS');
    }

    public static function getTrustedProxies(Request $request): ?array
    {
        $proxies = getenv('TRUSTED_PROXIES');
        if (! is_string($proxies)) {
            return null;
        }

        if (0 === strcasecmp(trim($proxies), 'all')) {
            return [
                '127.0.0.1',
                $request->server->get('REMOTE_ADDR'),
            ];
        }

        return array_map('trim', explode(',', $proxies));
    }

    public static function getUniqueFileName(File $file): string
    {
        return md5(uniqid()) . '.' . $file->guessExtension();
    }

    /**
     * Files in /tmp prefixed with "ucrmTmpFile" are deleted by cron after 3 days.
     */
    public static function getTemporaryFile(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'ucrmTmpFile');
        assert(is_string($tmpFile));

        return $tmpFile;
    }

    public static function getMessageId(\Swift_Mime_Message $message): string
    {
        $messageId = Strings::before($message->getId(), '@');
        assert(is_string($messageId));

        return $messageId;
    }

    public static function getDateFromYMD(?string $date): ?\DateTimeImmutable
    {
        if ($date && Strings::match($date, '/\d{4}-\d{2}-\d{2}/')) {
            try {
                $date = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
            } catch (\Exception $e) {
                return null;
            }
            $errors = \DateTimeImmutable::getLastErrors();

            return 0 === $errors['warning_count'] && 0 === $errors['error_count']
                ? $date
                : null;
        }

        return null;
    }

    public static function bytesToSize(float $bytes): string
    {
        $kilobyte = 1024;
        $megabyte = $kilobyte * 1024;
        $gigabyte = $megabyte * 1024;
        $terabyte = $gigabyte * 1024;

        if ($bytes < $kilobyte) {
            return sprintf('%s B', $bytes);
        }

        if ($bytes < $megabyte) {
            return sprintf('%0.2f KB', $bytes / $kilobyte);
        }

        if ($bytes < $gigabyte) {
            return sprintf('%0.2f MB', $bytes / $megabyte);
        }

        if ($bytes < $terabyte) {
            return sprintf('%0.2f GB', $bytes / $gigabyte);
        }

        return sprintf('%0.2f TB', $bytes / $terabyte);
    }

    /**
     * It is not possible to get exact file size from headers via function imap_fetchheader() so we assume it is
     * base64 which is about 33% larger.
     */
    public static function convertImapAttachmentBytes(int $imapAttachmentFilesize): int
    {
        return (int) ($imapAttachmentFilesize / 1.36844);
    }

    /**
     * Sanitizes a string for the use in JS flash message.
     *
     * - removes all HTML elements (except <a> and <br>)
     * - removes all attributes from <a> other than href and does not allow using "javascript:" pseudo-protocol in it
     */
    public static function sanitizeFlashMessage(string $message): string
    {
        // Wrapped in <span> to prevent creation of <p> by \DOMDocument.
        $message = sprintf(
            '<span>%s</span>',
            strip_tags(
                nl2br(trim($message)),
                '<a><br>'
            )
        );
        $message = Strings::replace(
            $message,
            '~\&rightarrow\;~',
            '&rarr;'
        );

        $dom = new \DOMDocument();
        $disableEntityLoaderState = libxml_disable_entity_loader(true);
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $message, LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntityLoaderState);
        /** @var DOMElement $tag */
        foreach ($dom->getElementsByTagName('*') as $tag) {
            foreach ($tag->attributes as $attribute) {
                if ($tag->nodeName === 'a' && $attribute->nodeName === 'href') {
                    if (Strings::startsWith($attribute->nodeValue, 'javascript:')) {
                        $attribute->nodeValue = urlencode($attribute->nodeValue);
                    }
                } else {
                    $tag->removeAttribute($attribute->nodeName);
                }
            }
        }

        // We need to take only contents of <body><span>, because LIBXML_HTML_NOIMPLIED is unstable.
        // See https://stackoverflow.com/a/44866403/7457614 for more information.
        return (string) Strings::before(
            (string) Strings::after(
                trim((string) $dom->saveHTML()),
                '<body><span>'
            ),
            '</span></body>'
        );
    }

    public static function generateNonce(): string
    {
        return Strings::replace(base64_encode(random_bytes(32)), '/[^a-zA-Z0-9]+/', '');
    }
}
