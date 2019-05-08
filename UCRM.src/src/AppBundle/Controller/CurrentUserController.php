<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\CurrentUserDataProvider;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class CurrentUserController extends BaseController
{
    /**
     * @var CurrentUserDataProvider
     */
    private $currentUserDataProvider;

    public function __construct(CurrentUserDataProvider $currentUserDataProvider)
    {
        $this->currentUserDataProvider = $currentUserDataProvider;
    }

    /**
     * @Permission("public")
     * @Route("/current-user", name="current_user")
     * @Method("GET")
     */
    public function defaultAction(): Response
    {
        $data = $this->currentUserDataProvider->getData();

        return $this->json($data, $data === null ? 403 : 200);
    }
}
