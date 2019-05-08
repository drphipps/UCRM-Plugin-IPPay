<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Annotation;

/**
 * @Annotation
 */
class Identifier
{
    /**
     * @Required
     *
     * @var string
     */
    public $id;
}
