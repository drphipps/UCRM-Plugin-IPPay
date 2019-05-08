<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class BackupFileName extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Backup filename is in a wrong format. Example of a valid format: backup_database.1000000000.2.0.0.tar.gz';
}
