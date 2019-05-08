<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\NetFlow;

class PacketParser
{
    /**
     * @var array
     */
    private $v9templates = [];

    /**
     * Parses NetFlow packet.
     *
     *
     *
     * @throws ParseErrorException
     */
    public function parse(string $packet, string $peer): \Generator
    {
        $length = strlen($packet);

        if ($length < 12) {
            throw new ParseErrorException(
                sprintf(
                    'NetFlow packet has invalid length %d.',
                    $length
                )
            );
        }

        list($version, $count) = $this->unpack($packet, 0, 'n2');

        switch ($version) {
            case 5:
                // Load relevant data from NetFlow v5 packet.
                // @link https://www.plixer.com/support/netflow_v5.html

                if ($length !== 24 + $count * 48) {
                    throw new ParseErrorException(
                        sprintf(
                            'NetFlow v5 packet has invalid length %d.',
                            $length
                        )
                    );
                }

                for ($i = 0; $i < $count; ++$i) {
                    $offset = 24 + $i * 48;
                    list($source, $target) = $this->unpack($packet, $offset, 'N2');
                    $bytes = $this->unpack($packet, $offset + 20, 'N')[0];

                    yield [$source, $target, $bytes];
                }

                break;

            case 9:
                // Parse Netflow v9 packet.
                // @link https://www.plixer.com/support/netflow_v9.html
                // @link https://github.com/delian/node-netflowv9/blob/master/js/nf9/nf9decode.js

                $offset = 20;
                $records = 0;
                $checkRecordsCount = true;

                while ($offset < $length) {
                    list($flowSetId, $flowSetLength) = $this->unpack($packet, $offset, 'n2');

                    switch (true) {
                        case $flowSetId === 0: // flow fields template
                            $this->parseV9Templates($packet, $peer, $offset, $flowSetLength, $records);
                            break;

                        case $flowSetId === 1: // option fields template
                            // not implemented
                            break;

                        case $flowSetId <= 255: // unknown
                            // do nothing
                            break;

                        case ! isset($this->v9templates[$peer][$flowSetId]): // data flowset with unknown template
                            $checkRecordsCount = false;
                            break;

                        default: // data flowset
                            yield from $this->parseV9Data(
                                $packet,
                                $offset,
                                $this->v9templates[$peer][$flowSetId],
                                $flowSetLength,
                                $records
                            );
                    }

                    $offset += $flowSetLength;
                }

                if ($offset !== $length) {
                    throw new ParseErrorException(
                        sprintf(
                            'Packet length mismatch (offset: %d, length: %d).',
                            $offset,
                            $length
                        )
                    );
                }

                if ($checkRecordsCount && $records !== $count) {
                    throw new ParseErrorException(
                        sprintf(
                            'Packet count mismatch (records: %d, count: %d).',
                            $records,
                            $count
                        )
                    );
                }

                break;

            default:
                throw new ParseErrorException(
                    sprintf(
                        'Netflow version %d is not supported.',
                        $version
                    )
                );
        }
    }

    /**
     * @throws ParseErrorException
     */
    private function parseV9Templates(string $packet, string $peer, int $offset, int $flowSetLength, int &$records)
    {
        $initialOffset = $offset;
        $offset += 4;

        while ($offset - $initialOffset < $flowSetLength) {
            list($templateId, $fieldCount) = $this->unpack($packet, $offset, 'n2');

            $template = [];
            $dataLength = 0;

            for ($i = 0; $i < $fieldCount; ++$i) {
                list($fieldType, $fieldLength) = $this->unpack($packet, $offset + 4 + $i * 4, 'n2');

                switch ($fieldType) {
                    case 1:
                        $template['bytes_offset'] = $dataLength;
                        $template['bytes_length'] = $fieldLength;
                        break;
                    case 8:
                        $template['v4src_offset'] = $dataLength;
                        break;
                    case 12:
                        $template['v4dst_offset'] = $dataLength;
                        break;
                }

                $dataLength += $fieldLength;
            }

            if (count($template) === 4) {
                $template['length'] = $dataLength;

                $this->v9templates[$peer][$templateId] = $template;
            }

            $offset += 4 + $fieldCount * 4;
            ++$records;
        }

        if ($offset - $initialOffset !== $flowSetLength) {
            throw new ParseErrorException(
                sprintf(
                    'Template length mismatch (offset: %d, length: %d).',
                    $offset - $initialOffset,
                    $flowSetLength
                )
            );
        }
    }

    /**
     * @throws ParseErrorException
     */
    private function parseV9Data(
        string $packet,
        int $offset,
        array $template,
        int $flowSetLength,
        int &$records
    ): \Generator {
        $initialOffset = $offset;
        $offset += 4;

        while ($offset - $initialOffset + $template['length'] <= $flowSetLength) {
            $source = $this->unpack($packet, $offset + $template['v4src_offset'], 'N')[0];
            $target = $this->unpack($packet, $offset + $template['v4dst_offset'], 'N')[0];

            if ($template['bytes_length'] === 4) {
                $bytes = $this->unpack($packet, $offset + $template['bytes_offset'], 'N')[0];
            } elseif ($template['bytes_length'] === 8) {
                $bytes = $this->unpack($packet, $offset + $template['bytes_offset'], 'J')[0];
            } else {
                throw new ParseErrorException(
                    sprintf(
                        'Unsupported bytes field length %s.',
                        $template['bytes_length']
                    )
                );
            }

            yield [$source, $target, $bytes];

            $offset += $template['length'];
            ++$records;
        }

        if ($offset - $initialOffset - $flowSetLength > 3) {
            throw new ParseErrorException(
                sprintf(
                    'Packet length mismatch (offset: %d, length: %d).',
                    $offset - $initialOffset,
                    $flowSetLength
                )
            );
        }
    }

    private function unpack(string $packet, int $offset, string $format): array
    {
        error_clear_last();
        $unpacked = @unpack(sprintf('@%d/%s', $offset, $format), $packet);
        if ($error = error_get_last()) {
            throw new ParseErrorException(
                sprintf(
                    '%s (offset: %d, format: %s).',
                    $error['message'],
                    $offset,
                    $format
                )
            );
        }

        return array_values($unpacked);
    }
}
