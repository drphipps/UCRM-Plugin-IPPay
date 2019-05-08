<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Email;

class EmailSanitizer
{
    public function sanitizeAddressFields(\Swift_Message $swiftMessage): void
    {
        $swiftMessage->setSender($this->getSanitizedAddresses($swiftMessage->getSender(), false));
        $swiftMessage->setReturnPath($this->getSanitizedAddresses($swiftMessage->getReturnPath(), false));

        $swiftMessage->setFrom($this->getSanitizedAddresses($swiftMessage->getFrom()));
        $swiftMessage->setReplyTo($this->getSanitizedAddresses($swiftMessage->getReplyTo()));
        $swiftMessage->setTo($this->getSanitizedAddresses($swiftMessage->getTo()));
        $swiftMessage->setCc($this->getSanitizedAddresses($swiftMessage->getCc()));
        $swiftMessage->setBcc($this->getSanitizedAddresses($swiftMessage->getBcc()));
    }

    private function getSanitizedAddresses($address, bool $allowMultiple = true): ?array
    {
        if (! $address) {
            return null;
        }

        // normalize addresses
        $addresses = [];
        if (is_string($address)) {
            $addresses[$address] = null;
        } elseif (is_array($address)) {
            foreach ($address as $key => $value) {
                if (is_string($key)) {
                    $email = $key;
                    $name = $value;
                } else {
                    $email = $value;
                    $name = null;
                }
                $addresses[$email] = $name;
            }
        } else {
            throw new \InvalidArgumentException();
        }

        // explode addresses
        $actualAddresses = [];
        foreach ($addresses as $email => $name) {
            $exploded = explode(' ;,', $email);

            // if there are more emails separated by delimiter, we throw away all but first
            if (count($exploded) > 1) {
                $actualAddresses[reset($exploded)] = $name;
            } else {
                $actualAddresses[$email] = $name;
            }
        }

        if (! $allowMultiple && count($actualAddresses) > 1) {
            foreach ($actualAddresses as $email => $name) {
                return [$email => $name];
            }
        }

        return $actualAddresses;
    }
}
