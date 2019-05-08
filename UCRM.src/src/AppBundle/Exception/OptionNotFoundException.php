<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Exception;

class OptionNotFoundException extends \OutOfBoundsException implements FlashMessageExceptionInterface
{
    use FlashMessageExceptionTrait;
}
