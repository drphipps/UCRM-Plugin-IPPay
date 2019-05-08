<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use Nette\Utils\Strings as NetteStrings;

class Strings
{
    public static function humanize(string $string): string
    {
        return ucfirst(strtolower(preg_replace('/(?<!^)([A-Z])/', ' \\1', $string)));
    }

    /**
     * @param array|string $address
     */
    public static function wrapAddress($address): string
    {
        if (is_array($address)) {
            $address = implode(', ', $address);
        }

        return wordwrap(
            htmlspecialchars($address ?? '', ENT_QUOTES),
            (int) round(NetteStrings::length($address) / 2),
            "<br>\n"
        );
    }

    public static function fixEncodingRecursive(array $data): array
    {
        array_walk_recursive(
            $data,
            function (&$item) {
                if (is_string($item)) {
                    $item = NetteStrings::fixEncoding($item);
                }
            }
        );

        return $data;
    }

    /**
     * Converts to web safe characters [a-z0-9-] text.
     */
    public static function slugify(string $value, ?string $charList = null, bool $lower = true): string
    {
        if ($lower) {
            $value = strtolower($value);
        }

        $value = preg_replace(
            '#[^a-z0-9' . ($charList !== null ? preg_quote($charList, '#') : '') . ']+#i',
            '-',
            $value
        );
        $value = trim($value, '-');

        return $value;
    }

    public static function slugifyCamelCase(string $value): string
    {
        return lcfirst(
            str_replace(
                '-',
                '',
                ucwords(
                    self::slugify($value),
                    '-'
                )
            )
        );
    }

    public static function sanitizeFileName(string $fileName): string
    {
        return NetteStrings::replace($fileName, '~\/|\\\\~', '_');
    }

    public static function removeUpTraverseFromFilePath(string $path): string
    {
        return NetteStrings::replace($path, '~\.\.\/~', '');
    }

    public static function stripAnsi(string $value): string
    {
        return NetteStrings::replace(
            $value,
            '/(\x9B|\x1B\[)[0-?]*[ -\/]*[@-~]/',
            ''
        );
    }

    public static function initials(string $name, string $separator = '', ?int $limit = 2): string
    {
        $name = array_map(
            function ($s) use ($separator) {
                return NetteStrings::substring($s, 0, 1) . $separator;
            },
            array_filter(explode(' ', $name))
        );

        return NetteStrings::upper(
            implode(
                '',
                array_slice($name, 0, $limit)
            )
        );
    }

    public static function surnameInitials(string $fullName): string
    {
        $fullName = array_filter(explode(' ', $fullName));
        $name = array_shift($fullName);

        return $name . ' ' . self::initials(implode(' ', $fullName), '. ', null);
    }

    public static function maskBankAccount($value): string
    {
        $delimiter = ':';
        $masked = [];
        foreach (explode($delimiter, $value) as $number) {
            $masked[] = str_repeat('*', max(0, strlen($number) - 4)) . substr($number, -4);
        }

        return implode($delimiter, $masked);
    }

    /**
     * Converts HTML to plain text.
     */
    public static function htmlToPlainText(string $html): string
    {
        return strip_tags(
            NetteStrings::replace(
                $html,
                [
                    sprintf(
                        '/[\\h​%s]+/u',
                        html_entity_decode('&#65279;')
                    ) => ' ', // squash horizontal whitespace to one space, &#65279; = ZERO WIDTH SPACE
                    "/[\r\n]/" => '', // strip endlines
                    '/<br[^>]*>/' => "\n", // make \n from <br>
                    '/<\/p>/' => "\n\n", // make \n\n from </p>
                    '/<\/h[1-6]>/' => "\n\n", // make \n\n from </h[1-6]>
                ]
            )
        );
    }
}
