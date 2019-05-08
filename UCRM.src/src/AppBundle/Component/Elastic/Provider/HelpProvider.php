<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\Provider;

use Elastica\Document;
use FOS\ElasticaBundle\Provider\PagerfantaPager;
use FOS\ElasticaBundle\Provider\PagerInterface;
use FOS\ElasticaBundle\Provider\PagerProviderInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class HelpProvider implements PagerProviderInterface
{
    /**
     * @var array
     */
    private $helpConfig;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(array $helpConfig, \Twig_Environment $twig)
    {
        $this->helpConfig = $helpConfig;
        $this->twig = $twig;
    }

    public function provide(array $options = []): PagerInterface
    {
        $documents = [];
        foreach ($this->helpConfig as $id => $item) {
            $documents[] = $this->createDocument($id, $item['name'], $item['template']);
        }

        return new PagerfantaPager(new Pagerfanta(new ArrayAdapter($documents)));
    }

    private function createDocument(string $id, string $name, string $template): Document
    {
        return new Document(
            $id,
            [
                'id' => $id,
                'helpName' => $name,
                'helpText' => trim(strip_tags($this->twig->render($template))),
            ]
        );
    }
}
