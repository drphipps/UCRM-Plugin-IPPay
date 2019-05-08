<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync\Exceptions;

class RemoteCommandException extends \Exception implements SyncException
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var iterable
     */
    private $arguments;

    /**
     * @param iterable $arguments
     */
    public function __construct(string $command, $arguments, string $message)
    {
        $this->command = $command;
        $this->arguments = $arguments;
        parent::__construct($message);
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return iterable
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
