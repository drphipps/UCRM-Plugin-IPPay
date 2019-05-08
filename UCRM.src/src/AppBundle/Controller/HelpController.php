<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\Component\Help\Help;
use AppBundle\Security\Permission;
use AppBundle\Util\Helpers;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/help")
 */
class HelpController extends BaseController
{
    /**
     * @Route("/{section}", name="help_index", defaults={"section" = null}, options={"expose"=true})
     * @Method("GET")
     * @Permission("public")
     */
    public function indexAction(Request $request, ?string $section): Response
    {
        if (! Helpers::isDemo()) {
            $this->denyAccessUnlessPermissionGranted(Permission::GUEST, self::class);
        } elseif (! $this->getUser()) {
            $showLeftPanel = false;
            $showHeader = false;
        }

        $path = $this->get(Help::class)->getTemplatePath($section);

        if (! $path) {
            throw $this->createNotFoundException();
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render($path);
        }

        return $this->render(
            'help/detail.html.twig',
            [
                'section' => $path,
                'showLeftPanel' => $showLeftPanel ?? true,
                'showHeader' => $showHeader ?? true,
            ]
        );
    }
}
