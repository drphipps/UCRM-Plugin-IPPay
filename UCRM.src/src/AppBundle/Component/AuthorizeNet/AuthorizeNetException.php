<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\AuthorizeNet;

use net\authorize\api\contract\v1\MessagesType\MessageAType;

class AuthorizeNetException extends \RuntimeException
{
    /**
     * @var array|MessageAType[]
     */
    private $messages = [];

    /**
     * @return array|MessageAType[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param array|MessageAType[] $messages
     */
    public function setMessages($messages): AuthorizeNetException
    {
        $this->messages = $messages;

        return $this;
    }
}
