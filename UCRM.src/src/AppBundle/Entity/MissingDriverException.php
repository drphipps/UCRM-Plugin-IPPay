<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use AppBundle\Sync\Exceptions\SyncException;

class MissingDriverException extends \Exception implements SyncException
{
}
