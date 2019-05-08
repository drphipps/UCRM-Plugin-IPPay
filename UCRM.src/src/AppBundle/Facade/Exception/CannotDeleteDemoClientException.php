<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade\Exception;

use AppBundle\Entity\Client;

class CannotDeleteDemoClientException extends \Exception implements ClientNotDeletedExceptionInterface
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        parent::__construct();

        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
