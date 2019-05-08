<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

class SandboxTerminationData
{
    /**
     * @deprecated since 2.14
     */
    public const MODE_DELETE = 1;

    /*
     * Factory reset.
     */
    public const MODE_ALL = 2;

    /*
     * End sandbox mode and remove some data as per settings below.
     */
    public const MODE_SMART = 3;

    /**
     * @var int
     */
    public $mode = self::MODE_SMART;

    /**
     * @var bool
     */
    public $keepClients = true;

    /**
     * @var bool
     */
    public $resetInvitationEmails = false;

    /**
     * @var bool
     */
    public $keepServices = true;

    /**
     * @var bool
     */
    public $keepInvoices = true;

    /**
     * @var bool
     */
    public $resetInvoiceEmails = false;

    /**
     * @var bool
     */
    public $resetNextInvoicingDay = false;

    /**
     * @var bool
     */
    public $keepPayments = true;

    /**
     * @var bool
     */
    public $keepTickets = true;

    /**
     * @var bool
     */
    public $keepJobs = true;
}
