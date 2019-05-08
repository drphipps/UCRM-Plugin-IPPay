<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Util\IpRangeParser;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable()
 */
class IpRange
{
    // Integer representation of maximum ip.
    // ip2long('255.255.255.255')
    const IP_MAX = 4294967295;

    /**
     * @var int
     *
     * @ORM\Column(name="ip_address", type="bigint")
     * @Assert\Range(min = 0, max = IpRange::IP_MAX)
     * @Assert\NotBlank()
     */
    protected $ipAddress;

    /**
     * @var int|null
     *
     * @ORM\Column(name="netmask", type="smallint", nullable=true)
     * @Assert\Range(
     *     min = 8,
     *     max = 32,
     *     minMessage = "Netmask must be {{ limit }} or more.",
     *     maxMessage = "Netmask must be {{ limit }} or less."
     * )
     */
    protected $netmask;

    /**
     * @var int
     *
     * @ORM\Column(name="first_ip_address", type="bigint")
     * @Assert\Range(min = 0, max = IpRange::IP_MAX)
     */
    protected $firstIp;

    /**
     * @var int
     *
     * @ORM\Column(name="last_ip_address", type="bigint")
     * @Assert\Range(min = 0, max = IpRange::IP_MAX)
     */
    protected $lastIp;

    public function getRangeForView(): string
    {
        return $this->formatRange(true);
    }

    public function getRange(): string
    {
        return $this->formatRange(false);
    }

    private function formatRange(bool $condenseAndEscape): string
    {
        if (null === $this->ipAddress) {
            return '';
        }

        if (null !== $this->netmask) {
            return sprintf('%s/%d', long2ip($this->getIpAddress()), $this->netmask);
        }

        if ($this->firstIp === $this->lastIp) {
            return long2ip($this->getIpAddress());
        }

        $first = long2ip($this->getFirstIp());
        $last = long2ip($this->getLastIp());
        if ($condenseAndEscape && Strings::before($first, '.', -1) === Strings::before($last, '.', -1)) {
            $last = Strings::after($last, '.', -1);
        }

        return sprintf(
            '%s%s%s',
            $first,
            $condenseAndEscape ? html_entity_decode('&#8209;') : '-',
            $last
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setRangeFromString(string $data): void
    {
        $data = str_replace(html_entity_decode('&#8209;'), '-', $data);

        $range = IpRangeParser::parse($data);

        if (! $range) {
            throw new \InvalidArgumentException(sprintf('Wrong IP range format ("%s")', $data));
        }

        switch ($range->type) {
            case IpRangeParser::FORMAT_SINGLE:
                $this->setSingle($range->ip);

                return;
            case IpRangeParser::FORMAT_CIDR:
                $this->setCidr($range->ip, $range->netmask);

                return;
            case IpRangeParser::FORMAT_RANGE:
                $this->setRange($range->first, $range->last);

                return;
        }

        throw new \InvalidArgumentException(sprintf('Wrong IP range format ("%s")', $data));
    }

    public function setSingle(int $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
        $this->firstIp = $ipAddress;
        $this->lastIp = $ipAddress;
        $this->netmask = null;
    }

    public function setRange(int $firstIp, int $lastIp): void
    {
        $this->ipAddress = $firstIp;
        $this->firstIp = $firstIp;
        $this->lastIp = $lastIp;
        $this->netmask = null;
    }

    public function setCidr(int $ipAddress, int $netmask): void
    {
        $this->ipAddress = $ipAddress;
        $this->netmask = $netmask;
        $networkSize = pow(2, 32 - $netmask);
        $this->firstIp = (int) floor($ipAddress / $networkSize) * $networkSize;
        $this->lastIp = $this->firstIp + $networkSize - 1;
    }

    public function getIpAddress(): ?int
    {
        return $this->castToInt($this->ipAddress);
    }

    public function getIpAddressString(): string
    {
        return long2ip($this->getIpAddress());
    }

    public function getNetmask(): ?int
    {
        return $this->netmask;
    }

    public function getFirstIp(): ?int
    {
        return $this->castToInt($this->firstIp);
    }

    public function getLastIp(): ?int
    {
        return $this->castToInt($this->lastIp);
    }

    public function getFormat(): ?int
    {
        if (null === $this->ipAddress) {
            return null;
        }

        if (null !== $this->netmask) {
            return IpRangeParser::FORMAT_CIDR;
        }
        if ($this->firstIp === $this->lastIp) {
            return IpRangeParser::FORMAT_SINGLE;
        }

        return IpRangeParser::FORMAT_RANGE;
    }

    /**
     * Casts string to integer but ignore null values. Needed because Doctrine uses PHP string for bigint values.
     *
     * @param string|int|null $ip
     */
    private function castToInt($ip = null): ?int
    {
        return null === $ip ? null : (int) $ip;
    }
}
