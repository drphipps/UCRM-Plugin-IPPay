<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Component;

/**
 * This class is used to provide correct routes for templates based on the controller, that is rendering them.
 * It is to be used only in TicketingActionsInterface|TicketingActionsTrait controllers.
 * Must be included as a parameter for every ticketing template render call.
 */
class TicketingRoutesMap
{
    /**
     * @var string
     */
    public $view;

    /**
     * @var string
     */
    public $delete;

    /**
     * @var string
     */
    public $deleteFromImap;

    /**
     * @var string
     */
    public $statusEdit;

    /**
     * @var string
     */
    public $ticketGroupEdit;

    /**
     * @var string
     */
    public $assign;

    /**
     * @var string
     */
    public $jobAdd;

    /**
     * @var string
     */
    public $jobRemove;

    /**
     * @var string
     */
    public $jobCreate;
}
