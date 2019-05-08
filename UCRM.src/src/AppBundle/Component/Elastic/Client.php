<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic;

use Elastica\Exception\ExceptionInterface;
use Elastica\Request;
use Elastica\Response;
use FOS\ElasticaBundle\Elastica\Client as BaseClient;

class Client extends BaseClient
{
    public function request(
        $path,
        $method = Request::GET,
        $data = [],
        array $query = [],
        $contentType = Request::DEFAULT_CONTENT_TYPE
    ): Response {
        try {
            return parent::request($path, $method, $data, $query);
        } catch (ExceptionInterface $e) {
            assert($e instanceof \Throwable);

            $this->_logger->warning(
                'Failed to send a request to Elasticsearch',
                [
                    'exception' => sprintf(
                        '%s:%d [%s] %s',
                        $e->getFile(),
                        $e->getLine(),
                        get_class($e),
                        $e->getMessage()
                    ),
                    'path' => $path,
                    'method' => $method,
                    'data' => $data,
                    'query' => $query,
                ]
            );

            return new Response('{"took":0,"timed_out":false,"hits":{"total":0,"max_score":0,"hits":[]}}');
        }
    }
}
