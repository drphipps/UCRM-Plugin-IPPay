<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class CsvColumn
{
    /**
     * Name of property in ImportItemValidationErrorsInterface to which this column maps.
     *
     * @var string
     *
     * @Required()
     */
    public $errorPropertyPath;

    /**
     * @var string
     *
     * @Required()
     */
    public $csvMappingField;

    /**
     * Defines label of mapping form input.
     *
     * @var string
     *
     * @Required()
     */
    public $label;

    /**
     * Defines description (help text) of mapping form input.
     *
     * @var string
     */
    public $description;

    /**
     * List of strings by which we can automatically recognize correct fields from raw CSV data.
     *
     * @var array<string>
     */
    public $automaticRecognition = [];
}
