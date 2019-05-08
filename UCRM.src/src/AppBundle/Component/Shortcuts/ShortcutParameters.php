<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Shortcuts;

use Symfony\Component\HttpFoundation\RequestStack;

class ShortcutParameters
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function get(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        return [
            'route' => $request->get('_route'),
            'parameters' => array_merge(
                $request->query->all(),
                $request->get('_route_params', [])
            ),
        ];
    }
}
