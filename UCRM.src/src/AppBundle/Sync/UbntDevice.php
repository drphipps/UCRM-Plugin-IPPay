<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync;

use AppBundle\Entity\DeviceInterface;
use AppBundle\Sync\Exceptions\UnrecognizedVersionCommandException;
use AppBundle\Util\Mac;
use Nette\Utils\Strings;

abstract class UbntDevice extends Device
{
    /**
     * @var int
     */
    protected $version;

    /**
     * @param array &$commands
     */
    final protected function callCommands(array &$commands): string
    {
        $delimiter = '#!!#';
        $cmd = [];
        foreach ($commands as $property => &$command) {
            $command['startDelimiter'] = sprintf('%s start %s %s', $delimiter, $property, $delimiter);
            $command['endDelimiter'] = sprintf('%s end %s %s', $delimiter, $property, $delimiter);
            if (! ($command['withoutDelimiter'] ?? false)) {
                $cmd[] = sprintf('echo "%s"', $command['startDelimiter']);
            }

            $cmd[] = $command['command'];
            if (! ($command['withoutDelimiter'] ?? false)) {
                $cmd[] = sprintf('echo "%s"', $command['endDelimiter']);
            }
        }
        unset($command);

        $cmd = array_map(
            function (string $command) {
                $command = trim($command);
                $format = '%s;';

                if (preg_match('~(.*)&$~imus', $command)) {
                    $format = '%s ';
                }

                return sprintf($format, $command);
            },
            $cmd
        );

        $cmds = rtrim(implode('', $cmd), ';');

        return Strings::fixEncoding($this->ssh->execute($cmds));
    }

    final protected function getInterfaceType(string $interfaceName): int
    {
        switch (true) {
            case Strings::match($interfaceName, '~([ae]th\d+\.\d+)~'):
                return DeviceInterface::TYPE_VLAN;
            case Strings::match($interfaceName, '~(eth\d+)~'):
                return DeviceInterface::TYPE_ETHERNET;
            case Strings::match($interfaceName, '~(br\d+)~'):
                return DeviceInterface::TYPE_BRIDGE;
            case Strings::match($interfaceName, '~(ath\d+)~'):
                return DeviceInterface::TYPE_WIRELESS;
            default:
                return DeviceInterface::TYPE_UNKNOWN;
        }
    }

    final protected function getInternalInterfaceType(string $interfaceName): string
    {
        return Strings::replace($interfaceName, '~[0-9]+~', '');
    }

    final protected function getInternalId(string $interfaceName): int
    {
        $index = Strings::replace($interfaceName, '~[a-z]+~', '');

        if (strpos($index, '.') !== false) {
            $explodedIndex = explode('.', $index);
            $index = $explodedIndex[0] * 100 + $explodedIndex[1];
        }

        $index = (int) $index;

        switch (true) {
            case strpos($interfaceName, 'ath') === 0:
                return 10000 + $index;
            case strpos($interfaceName, 'eth') === 0:
                return 20000 + $index;
            case strpos($interfaceName, 'br') === 0:
                return 30000 + $index;
            case strpos($interfaceName, 'switch') === 0:
                return 40000 + $index;
            default:
                return $index;
        }
    }

    /**
     * @throws UnrecognizedVersionCommandException
     */
    final protected function processVersionResponse(string $response)
    {
        preg_match('~\.v([\d]+)\.|^[v]?([\d]+)~imus', $response, $match);

        if (array_key_exists(1, $match)) {
            $this->version = (int) $match[1];

            $this->device->setOsVersion($response);
        } else {
            throw new UnrecognizedVersionCommandException('Unrecognized OS version.');
        }
    }

    /**
     * @param object    $entity
     * @param \stdClass $attributes
     */
    protected function updateEntityAttribute(
        $entity,
        $attributes,
        string $attributeName,
        string $method = null,
        bool $encryptValue = false
    ): bool {
        if (! property_exists($attributes, $attributeName)) {
            return false;
        }

        $getter = sprintf('get%s', ucwords($method ? $method : $attributeName));
        $setter = sprintf('set%s', ucwords($method ? $method : $attributeName));

        if ((! $encryptValue && $entity->$getter() !== $attributes->{$attributeName}) ||
            ($encryptValue && $this->encryption->decrypt($entity->$getter()) != $attributes->{$attributeName})
        ) {
            if ($encryptValue) {
                $attributes->{$attributeName} = $this->encryption->encrypt($attributes->{$attributeName});
            } else {
                $from = $entity->$getter();
                $to = $attributes->{$attributeName};

                switch ($attributeName) {
                    case self::MAC_ADDRESS:
                        $from = Mac::format($from);
                        $to = Mac::format($to);
                        break;
                    case self::SSID:
                    case self::ESSID:
                        $from = $this->sanitizeSSID($from);
                        $attributes->{$attributeName} = $to = $this->sanitizeSSID($to);
                        break;
                }

                list($from, $to) = $this->formatLogValues($attributeName, $from, $to, $attributes);
                $this->logChangedAttribute($entity, $attributeName, $from, $to, $method);
            }

            $entity->$setter($attributes->{$attributeName});

            return true;
        }

        return false;
    }
}
