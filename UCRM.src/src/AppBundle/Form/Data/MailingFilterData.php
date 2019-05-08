<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Entity\Client;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Collections are used instead of arrays because DoctrineType uses CollectionToArrayTransformer for multi choices (unlike standard ChoiceType).
 */
class MailingFilterData
{
    /**
     * @var Collection
     *
     * @Assert\Valid()
     */
    public $filterOrganizations;

    /**
     * @var array
     *
     * @Assert\All({
     *      @Assert\Choice(choices = {Client::TYPE_RESIDENTIAL, Client::TYPE_COMPANY}, strict = true)
     * })
     */
    public $filterClientTypes;

    /**
     * @var bool|null
     */
    public $filterIncludeLeads = false;

    /**
     * @var Collection
     *
     * @Assert\Valid()
     */
    public $filterClientTags;

    /**
     * @var Collection
     *
     * @Assert\Valid()
     */
    public $filterServicePlans;

    /**
     * @var Collection
     *
     * @Assert\Valid()
     */
    public $filterPeriodStartDays;

    /**
     * @var Collection
     *
     * @Assert\Valid()
     */
    public $filterSites;

    /**
     * @var Collection
     *
     * @Assert\Valid()
     */
    public $filterDevices;
}
