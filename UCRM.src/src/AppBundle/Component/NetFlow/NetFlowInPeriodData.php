<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\NetFlow;

class NetFlowInPeriodData
{
    /**
     * @var \DateTimeImmutable|null
     */
    private $invoicedFrom;

    /**
     * @var \DateTimeImmutable|null
     */
    private $invoicedTo;

    /**
     * @var int bytes
     */
    private $download = 0;

    /**
     * @var int bytes
     */
    private $upload = 0;

    public function getInvoicedFrom(): ?\DateTimeImmutable
    {
        return $this->invoicedFrom;
    }

    public function setInvoicedFrom(?\DateTimeImmutable $invoicedFrom): void
    {
        $this->invoicedFrom = $invoicedFrom;
    }

    public function getInvoicedTo(): ?\DateTimeImmutable
    {
        return $this->invoicedTo;
    }

    public function setInvoicedTo(?\DateTimeImmutable $invoicedTo): void
    {
        $this->invoicedTo = $invoicedTo;
    }

    public function addDownload(int $download): void
    {
        $this->download += $download;
    }

    public function getDownload(): int
    {
        return $this->download;
    }

    public function setDownload(int $download): void
    {
        $this->download = $download;
    }

    public function addUpload(int $upload): void
    {
        $this->upload += $upload;
    }

    public function getUpload(): int
    {
        return $this->upload;
    }

    public function setUpload(int $upload): void
    {
        $this->upload = $upload;
    }
}
