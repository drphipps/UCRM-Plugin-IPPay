<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Entity\General;
use AppBundle\Security\Permission;
use AppBundle\Service\Options;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 */
class VersionController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Options
     */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    /**
     * @Get("/version", name="version_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("public")
     */
    public function getAction(): View
    {
        return $this->view(
            [
                'version' => $this->options->getGeneral(General::CRM_INSTALLED_VERSION),
            ]
        );
    }
}
