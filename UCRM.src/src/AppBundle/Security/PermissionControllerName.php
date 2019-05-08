<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Sets for which controller the permissions should be required.
 *
 * For example:
 * if @PermissionControllerName(InvoiceController::class) is set on BillingController,
 * user will need permission for InvoiceController to access BillingController
 *
 * @Annotation
 */
class PermissionControllerName
{
    /**
     * @var string
     */
    private $controller;

    public function __construct(array $options)
    {
        if (class_exists($options['value']) && new $options['value']() instanceof Controller) {
            $this->controller = $options['value'];
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'PermissionControllerName has to be a controller name! Added `%s`',
                    $options['value']
                )
            );
        }
    }

    public function getController(): string
    {
        return $this->controller;
    }
}
