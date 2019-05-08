<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/training")
 */
class HeaderTrainingController extends BaseController
{
    /**
     * @Route("", name="header_training_index_modal")
     * @Method("GET")
     * @Permission("guest")
     */
    public function indexAction(): Response
    {
        return $this->render(
            '_demo/training_modal.html.twig'
        );
    }
}
