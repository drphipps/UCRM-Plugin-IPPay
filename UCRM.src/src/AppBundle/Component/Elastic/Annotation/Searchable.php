<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Methods with this annotation are included in Elasticsearch index. Used for navigation search.
 * MUST be used alongside Route() annotation.
 *
 * @Annotation
 * @Target("METHOD")
 */
class Searchable
{
    /**
     * Section heading, e.g. "Application".
     *
     * @Required()
     *
     * @var string
     */
    public $heading;

    /**
     * Path to the UCRM section, e.g. "System -> Settings -> Application".
     * The path is split by " -> " delimiter, each part is translated and then merged back with " → " glue.
     *
     * @Required()
     *
     * @var string
     */
    public $path;

    /**
     * Array of form type classes from which the labels are taken automatically.
     *
     * @var array
     */
    public $formTypes;

    /**
     * Extra labels by which this route can be found.
     * E.g. when something is not in form labels, but we still want it searchable.
     *
     * @var array
     */
    public $extra;
}
