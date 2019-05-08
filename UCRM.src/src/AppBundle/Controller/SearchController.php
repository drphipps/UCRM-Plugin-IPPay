<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\MultiSearch;
use AppBundle\Component\Elastic\Search;
use AppBundle\Component\Elastic\SearchUtilities;
use AppBundle\Exception\ElasticsearchException;
use AppBundle\Security\Permission;
use Elastica\Exception\ConnectionException;
use Elastica\Exception\ResponseException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/search")
 */
class SearchController extends BaseController
{
    /**
     * @Route("/", name="search_everywhere", options={"expose": true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function searchEverywhereAction(Request $request): JsonResponse
    {
        $results = [];

        $term = $request->get('term');
        $term = is_string($term) ? trim($term) : null;
        if (! empty($term)) {
            try {
                $results = $this->get(MultiSearch::class)->search($term);
            } catch (ResponseException | ConnectionException | ElasticsearchException $exception) {
                return new JsonResponse(
                    [
                        'error' => $this->trans(
                            'Could not process search because of Elasticsearch error: %error%',
                            [
                                '%error%' => $exception->getMessage(),
                            ]
                        ),
                    ]
                );
            }
        }

        return new JsonResponse($results);
    }

    /**
     * @Route("/client", name="search_additional_client", options={"expose": true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function searchAdditionalClientAction(Request $request): JsonResponse
    {
        $results = [];

        $term = $request->get('term');
        $term = is_string($term) ? trim($term) : null;
        if (! empty($term)) {
            try {
                $results = $this->get(Search::class)->search(Search::TYPE_CLIENT, $term);
            } catch (ResponseException | ConnectionException | ElasticsearchException $exception) {
                return new JsonResponse(
                    [
                        'error' => $this->trans(
                            'Could not process search because of Elasticsearch error: %error%',
                            [
                                '%error%' => $exception->getMessage(),
                            ]
                        ),
                    ]
                );
            }
        }

        return new JsonResponse($this->additionalClientTransform($results));
    }

    private function additionalClientTransform(array $results): array
    {
        $response = [];

        foreach ($results as $client) {
            $response[] = [
                'label' => $client->getNameForView(),
                'description' => $this->get(SearchUtilities::class)->getClientDescription($client),
                'id' => $client->getId(),
                'organization' => $client->getOrganization()->getName(),
            ];
        }

        return $response;
    }
}
