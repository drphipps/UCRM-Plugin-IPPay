<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use Nette\Utils\Json;
use Nette\Utils\Strings;

class VyattaParser
{
    public static function parse(string $input): array
    {
        $parser = new self();

        // We need to strip out the ASCII control characters, else we get invalid JSON.
        // However, there are several that we do want.
        // see e.g. https://www.bennadel.com/blog/2576-testing-which-ascii-characters-break-json-javascript-object-notation-parsing.htm
        $stripCharacters = [];
        for ($i = 0; $i <= 31; ++$i) {
            $stripCharacters[chr($i)] = '';
        }
        $stripCharacters[chr(9)] = ' '; // tab to space
        $stripCharacters[chr(12)] = ' '; // form feed to space
        unset($stripCharacters[chr(10)]); // do not remove LF
        unset($stripCharacters[chr(13)]); // do not remove CR

        $input = strtr(
            $parser->trim($input),
            $stripCharacters
        );
        if (! $input) {
            return [];
        }

        $output = Strings::replace(
            strtr(
                $parser->convertToJson($input),
                [
                    chr(10) => ' ',
                    chr(13) => ' ',
                ]
            ),
            // this deals with rogue endlines sneaking into data
            '~,{2,}~',
            ','
        );

        return Json::decode($output, Json::FORCE_ARRAY);
    }

    private function trim(string $input): string
    {
        $indexOfFirstParenthesis = Strings::indexOf($input, '{');
        $indexOfLastParenthesis = Strings::indexOf($input, '}', -1);

        if ($indexOfFirstParenthesis === false || $indexOfLastParenthesis === false) {
            return '';
        }

        $start = Strings::indexOf(Strings::substring($input, 0, $indexOfFirstParenthesis ?: 0), "\n", -1) ?: 0;
        $length = ($indexOfLastParenthesis ?: 0) + 1;

        return Strings::trim(
            Strings::substring($input, $start, $length - $start)
        );
    }

    private function convertToJson(string $input): string
    {
        $json = '';
        $lastKey = null;
        $lastKeyIndex = 0;

        $inputArray = explode("\n", $input);
        foreach ($inputArray as $i => $inputRow) {
            $inputRow = Strings::trim($inputRow);

            if ($matches = Strings::match($inputRow, '/^([\w\s-]+)\s+{$/')) {
                $json .= sprintf(
                    '"%s":{',
                    Strings::replace($matches[1], '/\s+/', '_')
                );
            } elseif ($matches = Strings::match($inputRow, '/^([\w\s-]+)\s+(.+)\s+{$/')) {
                $json .= sprintf(
                    '"%s_%s":{',
                    $matches[1],
                    $matches[2]
                );
            } else {
                if ($matches = Strings::match($inputRow, '/^([\w-]+)\s+(.+)$/')) {
                    $key = $matches[1];
                    if ($key === $lastKey) {
                        $key .= '_' . $lastKeyIndex;
                        ++$lastKeyIndex;
                    } else {
                        $lastKey = $key;
                        $lastKeyIndex = 0;
                    }

                    $json .= sprintf(
                        '"%s":"%s"',
                        $key,
                        addslashes(Strings::trim($matches[2], '\'"'))
                    );
                } elseif ($matches = Strings::match($inputRow, '/^([\w-]+)$/')) {
                    $key = $matches[1];
                    if ($key === $lastKey) {
                        $key .= '_' . $lastKeyIndex;
                        ++$lastKeyIndex;
                    } else {
                        $lastKey = $key;
                        $lastKeyIndex = 0;
                    }

                    $json .= sprintf('"%s":true', $key);
                } elseif ($inputRow === '}') {
                    $json .= $inputRow;
                    $lastKey = null;
                    $lastKeyIndex = 0;
                }

                $nextInputRow = array_key_exists($i + 1, $inputArray)
                    ? Strings::trim($inputArray[$i + 1])
                    : null;

                if ($nextInputRow && $nextInputRow !== '}') {
                    $json .= ',';
                }
            }
        }

        return sprintf('{%s}', $json);
    }
}
